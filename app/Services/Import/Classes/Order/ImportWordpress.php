<?php

namespace App\Services\Import\Classes\Order;

use App\Exceptions\importException;
use App\Exceptions\integrationException;
use App\Models\PerAccountsModels\MongoModels\Customer;
use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Models\ImportOrderModel;
use Carbon\Carbon;
use Mockery\Exception;

class ImportWordpress extends BaseImportClass implements ImportImplementationInterface
{
    const PER_PAGE = 10;

    private $lastIdFlag = false;

    public function prepare($importId)
    {
        parent::prepare($importId);
        $offset = 0;
        while (count($orders = $this->getOrders($offset)) > 0) {
            if (!$this->lastIdFlag) {
                $this->setLastId($orders[0]->id);
            }

            foreach ($orders as $order) {
//                /**
//                 * @var $model ImportOrderModel
//                 */
                $model = $this->storage->getItemModel();
//
                $model->id = $order->id;
                $model->order_date = (string)(Carbon::parse($order->date_created));
                $model->shop_id = $this->shop->_id;
                $model->customer_id = $this->getCustomer($order)->_id;
                $model->delivery_info = $this->getDeliveryInfo($order);
                $model->payment_info = $this->getPaymentInfo($order);
                $model->products = $this->getProducts($order);
//
                $this->storage->pushDirty($model);
            }
            $offset += self::PER_PAGE;
        }
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

    /*
     * @param $order
     * @return Customer
     */
    public function getCustomer($order)
    {
        $billing = $order->billing;
        $phone = formatPhoneNumber($billing->phone);
        if ($customer = $this->customerRepository->findBy('phone', 'like', $phone)) {
            return $customer;
        } else {
            $data = [
                'name' => $billing->first_name.' '.$billing->last_name,
                'shop_id' => $this->shop->_id,
                'status' => $this->customerRepository::STATUS_ACTIVE,
                'external_id' => ($order->customer_id > 0) ? $order->customer_id : null,
                'email' => $billing->email,
                'phone' => $phone,
            ];
            return $this->customerRepository->create($data);
        }
    }

    /**
     * @param $order
     * @return string
     */
    public function getDeliveryInfo($order)
    {

        $shipping =  $order->shipping;
        return implode(', ', [
            $shipping->first_name,
            $shipping->last_name,
            $shipping->company,
            $shipping->country,
            $shipping->state,
            $shipping->city,
            $shipping->postcode,
            $shipping->address_1,
            $shipping->address_2,
            $shipping->postcode,
        ]);
    }

    /**
     * @param $order
     * @return string
     */
    public function getPaymentInfo($order)
    {
        $billing = $order->billing;
        return implode(', ', [
            $order->payment_method,
            $billing->first_name,
            $billing->last_name,
        ]);
    }

    /**
     * @param $order
     * @return array
     */
    public function getProducts($order)
    {
        $products = [];
        foreach ($order->line_items as $orderProduct) {

            $product = $this->productRepository->findByShopProductId($this->shop->_id, (string)$orderProduct->product_id);

            if ($product) {
                $products[] = [
                    'product_id' => $product->_id,
                    'quantity' => $orderProduct->quantity,
                    'price' => $orderProduct->price,
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
            if (isset($response)) {
                return $response;
            }
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }
    }
}