<?php
/**
 * Created by PhpStorm.
 * User: nastia
 * Date: 22.02.18
 * Time: 16:10
 */

namespace App\Services\Import\Classes\Order;

use App\Exceptions\importException;
use App\Exceptions\integrationException;
use App\Models\PerAccountsModels\MongoModels\Customer;
use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Models\ImportOrderModel;

class ImportMagento extends BaseImportClass implements ImportImplementationInterface
{
    const PER_PAGE = 10;

    private $lastIdFlag = false;
    /**
     * @param string $importId
     * @return mixed|void
     * @throws importException
     */
    public function prepare($importId)
    {
        parent::prepare($importId);
        $offset = 1;
        $counter = 0;
        do {

            $orders = $this->getOrders($offset);
            if (!$this->lastIdFlag) {
                if(checkArrayIndexes($orders, 'items', 0, 'entity_id')) {
                    $this->setLastId($orders['items'][0]['entity_id']);
                }
                elseif(checkArrayIndexes($orders, 'items', 'entity_id')) {
                    $this->setLastId($orders['items']['entity_id']);
                }
            }
            $counter += count($orders['items']);
            if (!isset($total_count)) {
                $total_count = $orders['total_count'];
            }
            foreach ($orders['items'] as $order) {
                /**
                 * @var $model ImportOrderModel
                 */
                $model = $this->storage->getItemModel();

                $model->id = $order['entity_id'];
                $model->order_date = $order['created_at'];
                $model->comment = $order['customer_note'] ?? '';
                $model->shop_id = $this->shop->_id;
                $model->customer_id = $this->getCustomer($order)->_id;
                $model->delivery_info = $this->getDeliveryInfo($order);
                $model->payment_info = $this->getPaymentInfo($order);
                $model->products = $this->getProducts($order);
                $this->storage->pushDirty($model);
            }
            $offset++;
        }
        while($counter < $total_count);
    }

    private function setLastId($id)
    {
        if ($this->lastIdFlag) {
            return;
        }
        $this->shop->last_order_id = $id ;
        $this->shop->save();
        $this->lastIdFlag = true;
    }

    /**
     * @param array $order
     * @return Customer
     */
    public function getCustomer($order)
    {
        $phonesInOrder = [];
        $phone = '';
        array_walk_recursive($order, function ($item, $key) use (&$phonesInOrder) {
            if ($key === 'telephone') {
                $phonesInOrder[] = $item;
            }
        });

        if (!empty($phonesInOrder)) {
            foreach ($phonesInOrder as $key => $number) {
                $phone = formatPhoneNumber($number);
                $customer = $this->customerRepository->findBy('phone', 'like', $phone);
            }
        }

        if ($customer) {
            return $customer;
        } else {
            $data = [
                'name' => $order['customer_firstname'] . ' ' . $order['customer_lastname'],
                'shop_id' => $this->shop->_id,
                'status' => $this->customerRepository::STATUS_ACTIVE,
                'external_id' => ($order['customer_id'] > 0) ? $order['customer_id'] : null,
                'email' => $order['customer_email'],
                'phone' => $phone,
            ];
            return $this->customerRepository->create($data);
        }
    }


    /**
     * @param array $order
     * @return string
     */
    public function getDeliveryInfo($order)
    {
        if (!checkArrayIndexes($order, 'extension_attributes', 'shipping_assignments', 0, 'shipping')) {
            return '';
        }
        $shipmentInOrder = $order['extension_attributes']['shipping_assignments'][0];
        return implode(', ', [
            $order['shipping_description'] ?? '',
            $shipmentInOrder['shipping']['address']['firstname'] ?? '',
            $shipmentInOrder['shipping']['address']['lastname'] ?? '',
            $shipmentInOrder['shipping']['address']['country_id'] ?? '',
            $shipmentInOrder['shipping']['address']['region'] ?? '',
            $shipmentInOrder['shipping']['address']['city'] ?? '',
            implode(', ', $shipmentInOrder['shipping']['address']['street'] ?? []),
            $shipmentInOrder['shipping']['address']['postcode'] ?? '',
        ]);
    }

    /**
     * @param array $order
     * @return string
     */
    public function getPaymentInfo($order)
    {
        if (!checkArrayIndexes($order, 'payment', 'additional_information')) {
            return '';
        }
        if (!checkArrayIndexes($order, 'billing_address')) {
            return '';
        }

        return implode(', ', [
            implode( '', $order['payment']['additional_information']),
            $order['billing_address']['firstname'] ?? '',
            $order['billing_address']['lastname'] ?? '',
        ]);

    }

    /**
     * @param array $order
     * @return array
     */
    public function getProducts($order)
    {
        $products = [];
        $items = $order['items'];
        foreach ($items as $orderProduct) {
            if ($orderProduct['product_type'] != 'simple') {
                continue;
            }
            if ($product = $this->productRepository->findByShopProductId($this->shop->_id, (string)$orderProduct['product_id'])) {
                $products[] = [
                    'product_id' => $product->_id,
                    'quantity' => $orderProduct['qty_ordered'],
                    'price' => $orderProduct['price'] ??
                        $orderProduct['original_price'] ??
                        $orderProduct['row_total'] ??
                        $orderProduct['base_price'] ??
                        $orderProduct['base_original_price'] ??
                        $orderProduct['base_row_total'] ??
                        ''
                ];
            }

        }
        return $products;
    }

    /**
     * @param int $offset
     * @return array
     * @throws importException
     */
    private function getOrders($offset) {
        try {
            $from_date = ($this->shop->last_order_update) ? $this->shop->last_order_update : $this->shop->created_at;
            $response = $this->integration->order()->getAll((string)$from_date, $offset, self::PER_PAGE);
            if (isset($response['items'])) {
                return $response;
            }
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }
    }

}