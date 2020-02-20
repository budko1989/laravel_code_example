<?php
namespace App\Services\Import\Classes\Order;

use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Contracts\ImportImplementationInterface;
use SimpleXMLElement;
use App\Exceptions\importException;
use App\Repositories\PerAccountsRepositories\Contracts\OrderRepositoryInterface;
use App\Services\Integration\PrestashopIntegration;

class ImportPrestashop extends BaseImportClass implements ImportImplementationInterface
{
    const LIMIT = 10;

    public function prepare($importId)
    {
        parent::prepare($importId);

        $offset = 0;

        while (count($orders = $this->getOrders($offset)) > 0) {
            foreach ($orders as $order) {
                $order->state = $this->integration->getConnection()->get([
                    'resource' => 'order_states',
                    'id' => $order->current_state,
                    'output_format' => 'JSON'
                ])->state;

                /**
                 * @var ImportOrderModel $model
                 */
                $model = $this->storage->getItemModel();

                $model->id = $order->id;
                $model->order_date = $order->date_add;
                $model->comment = '';
                $model->shop_id = $this->shop->_id;
                $model->customer_id = $this->handleCustomerIdColumn($order);
                $model->delivery_info = $this->handleDeliveryInfoColumn($order);
                $model->payment_info = $this->handlePaymentInfoColumn($order);
                $model->products = $this->handleProductsColumn($order);
                // $model->currency_id = $this->handleCurrencyIdColumn($order);
                $model->payment_type = $this->handlePaymentTypeColumn($order);
                $model->payment_status = $this->handlePaymentStatusColumn($order);

                $this->storage->pushDirty($model);
            }

            $offset += self::LIMIT;
        }

        if (isset($order)) {
            $this->shop->last_order_id = (int) $order->id;
            $this->shop->save();
        }
    }

    /**
     * @param $order  Order data from the remote channel
     * @return string       Handled column Value.
     */
    private function handleCustomerIdColumn($order) : string
    {
        /**
         * @var \SimpleXMLElement $xmlAdresses
         */
        $xmlAdresses = $this->getAddressList($order->id_customer);

        /**
         * @var \SimpleXMLElement $xmlCustomer
         */
        $xmlCustomer = $this->getCustomerData($order->id_customer);

        $phoneList = [];
        $addressNode = $xmlAdresses->address[0];

        foreach($xmlAdresses as $item) {
            $phoneList[] = trim($item->phone);
        }

        $phoneList = array_unique($phoneList);

        $customer = $this->customerRepository->getByPhoneSet($phoneList);

        if (!$customer) {
            $customer = $this->customerRepository->create([
                'name' => sprintf('%s %s', trim($addressNode->firstname), trim($addressNode->lastname)),
                'shop_id' => $this->shop->_id,
                'status' => $this->customerRepository::STATUS_ACTIVE,
                'external_id' => $order->id_customer,
                'email' => trim($xmlCustomer->email),
                'phone' => trim($addressNode->phone ?: $addressNode->phone_mobile),
            ]);
        }

        return $customer->_id;
    }

    /**
     * @param $order  Order data from the remote channel
     * @return string       Handled column Value.
     */
    private function handleDeliveryInfoColumn($order) : string
    {
        $items = [];
        try {
            $xmlAddress = $this->integration->getConnection()->get([
                'resource' => 'addresses',
                'id' => $order->id_address_delivery
            ])->children()->children();

        } catch (\Exception $exception) {
            return '';
        }

        try {
            $xmlCountry = $this->integration->getConnection()->get([
                'resource' => 'countries',
                'id' => (int) $xmlAddress->id_country
            ])->children()->children();
            $items[] = trim((string) $xmlCountry->name->language[0]);
        } catch (\Exception $exception) {}
        try {
            $xmlCarrier = $this->integration->getConnection()->get([
                'resource' => 'carriers',
                'id' => $order->id_carrier
            ])->children()->children();
            $items[] = trim((string) $xmlCarrier->name);
        } catch (\Exception $exception) {}
        try {
            $xmlZone = $this->integration->getConnection()->get([
                'resource' => 'zones',
                'id' => (int) $xmlCountry->id_zone
            ])->children()->children();
            $items[] =  trim((string) $xmlZone->name);
        } catch (\Exception $exception) {}
        try {
            $xmlState = $this->integration->getConnection()->get([
                'resource' => 'states',
                'id' => (int) $xmlAddress->id_state
            ])->children()->children();
            $items[] = trim((string) $xmlState->name);
        } catch (\Exception $exception) {}

        $items[] = trim((string) $xmlAddress->city);
        $items[] = trim((string) $xmlAddress->address1);
        $items[] = trim((string) $xmlAddress->address2);
        $items[] = trim((string) $xmlAddress->postcode);
        $items[] = trim((string) $xmlAddress->phone) ?: trim((string) $xmlAddress->phone_mobile);

        return 'METHOD: '.implode(', ', $items);

    }

    private function handlePaymentInfoColumn($order) : string
    {
        /**
         * @var \SimpleXMLElement $xml
         */
        $xmlCustomer = $this->getCustomerData($order->id_customer);

        $firstname = (string) $xmlCustomer->firstname;
        $lastname = (string) $xmlCustomer->lastname;
        $additional = $order->payment;

        return sprintf('%s %s, %s',
            $firstname,
            $lastname,
            $additional
        );
    }

    private function handleProductsColumn($order) : array
    {
        $result = [];

        foreach ($order->associations->order_rows as $item) {
            $product = $this->productRepository->findByShopProductId(
                $this->shop->_id,
                (string) $item->product_id
            );

            if ($product) {
                $result[] = [
                    'product_id' => $product->_id,
                    'quantity' => $item->product_quantity,
                    'price' => $item->product_price,
                ];
            }
        }

        \Log::debug('ImportPrestashop. Products added', [
            'order_id' => $order->id,
            'count' => count($result)
        ]);

        return $result;
    }

    /*public function handleCurrencyIdColumn($order) : string
    {
        $prestaCurrency = PrestashopIntegration::toItem($this->integration->getConnection()->get([
            'resource' => 'currencies',
            'id' => $order['currency_id']
        ])->children()->children());

        $currency = Currency::whereCode($prestaCurrency['iso_code'])->first();

        if (!$currency) {
            throw new importException("currency_is_not_defined ({$prestaCurrency['iso_code']})");
        }

        return $currency->_id;
    }*/

    private function handlePaymentTypeColumn($order) : int
    {
        switch ($order->module) {
            case 'ps_wirepayment':
                return OrderRepositoryInterface::PAYMENT_TYPE_PREPAYMENT;

            case 'ps_cashondelivery':
                return OrderRepositoryInterface::PAYMENT_TYPE_COD;

//            case 'ps_checkpayment':
//                return OrderRepositoryInterface::PAYMENT_TYPE_BY_CHECK;

            default:
                return OrderRepositoryInterface::PAYMENT_TYPE_PREPAYMENT;
                break;
        }
    }


    private function handlePaymentStatusColumn($order) : int
    {
        return $order->state->paid == 1 ?
            OrderRepositoryInterface::PAYMENT_STATUS_PAID :
            OrderRepositoryInterface::PAYMENT_STATUS_NOT_PAID;
    }

    /**
     * Retrieve a list of the orders
     *
     * @param int $offset
     * @return array
     */
    private function getOrders(int $offset = 0) : array
    {
        $result = [];

        $orders = $this->integration->order()->getAll(null, $offset, self::LIMIT);

        if ($this->shop->last_order_id) {
            foreach ($orders as $item) {
                if ($item->id == $this->shop->last_order_id) {
                    break;
                }

                $result[] = $item;
            }
        } else {
            $result = $orders;
        }

        return $result;
    }

    /**
     * Retrieve raw Data of the customer
     *
     * @param int $customer_id
     * @return SimpleXMLElement
     */
    private function getCustomerData(int $customer_id) : SimpleXMLElement
    {
        return $this->integration->getConnection()->get([
            'resource' => 'customers',
            'id' => $customer_id
        ])->children()->children();
    }

    private function getAddressList(int $customer_id) : SimpleXMLElement
    {
        return $this->integration->getConnection()->get([
            'resource' => 'addresses',
            'filter[id_customer]' => $customer_id,
            'display' => 'full'
        ])->children()->children();
    }
}