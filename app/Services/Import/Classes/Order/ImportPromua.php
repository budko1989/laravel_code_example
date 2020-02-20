<?php
/**
 * Created by PhpStorm.
 * User: nastia
 * Date: 05.03.18
 * Time: 14:41
 */

namespace App\Services\Import\Classes\Order;

use App\Exceptions\importException;
use App\Exceptions\integrationException;
use App\Models\PerAccountsModels\MongoModels\Customer;
use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Models\ImportOrderModel;
use Carbon\Carbon;
use App\Services\Delivery\AbstractDelivery;
use App\Repositories\PerAccountsRepositories\Contracts\OrderRepositoryInterface;

/**
 * Class ImportPromua
 * @package App\Services\Import\Classes\Order
 */
class ImportPromua extends BaseImportClass implements ImportImplementationInterface
{
    const PER_PAGE = 10;

    /**
     * Prom UA Delivery types
     */
    const PICKUP = "Самовывоз";
    const POST = "Доставка почтой";
    const COURIER = "Доставка курьером Prime!";

    /**
     * Prom UA Payment types
     */
    const CASH = "Наличными";
    const CASHLESS = "Безналичный расчет";
    const PRIVAT24 = "Приват24";
    const COD = "Наложенный платеж";

    /**
     * @var bool
     */
    private $lastIdFlag = false;

    /**
     * @param string $importId
     * @return mixed|void
     * @throws importException
     */
    public function prepare($importId)
    {
        parent::prepare($importId);
        $offset = 0;
        while (count($orders = $this->getOrders($offset)) > 0) {
            if (!$this->lastIdFlag) {
                $this->setLastId($orders[0]['id']);
            }
            foreach ($orders as $order) {
                /**
                 * @var $model ImportOrderModel
                 */
                $model = $this->storage->getItemModel();

                $model->id = $order['id'];
                $model->order_date = (string)(Carbon::parse($order['date_created']));
                $model->comment = $order['client_notes'] ?? '';
                $model->shop_id = $this->shop->_id;
                $model->customer_id = $this->getCustomer($order)->_id;
                $model->delivery_info = $this->getDeliveryInfo($order);
                $model->payment_info = $this->getPaymentInfo($order);
                $model->products = $this->getProducts($order);
                $model->delivery_type = $this->parseDeliveryType($order);
                $model->payment_type = $this->parsePaymentType($order);
                $this->storage->pushDirty($model);
                $offset = $order['id'];
            }

        }
    }

    /**
     * @param int $id
     */
    private function setLastId(int $id)
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
    public function getCustomer(array $order)
    {

        $phone = formatPhoneNumber($order['phone']);
        $customer = $this->customerRepository->findBy('phone', 'like', $phone);
        if ($customer) {
            return $customer;
        } else {
            $data = [
                'name' => $order['client_first_name'] . ' ' . $order['client_last_name'],
                'shop_id' => $this->shop->_id,
                'status' => $this->customerRepository::STATUS_ACTIVE,
                'external_id' => ($order['client_id'] > 0) ? $order['client_id'] : null,
                'email' => $order['email'],
                'phone' => $phone,
            ];
            return $this->customerRepository->create($data);
        }
    }


    /**
     * @param array $order
     * @return string
     */
    public function getDeliveryInfo(array $order)
    {
        return implode(', ', [
            implode(', ', $order['delivery_option'] ?? []),
            $order['delivery_address'] ?? [],
        ]);
    }

    /**
     * @param array $order
     * @return string
     */
    public function getPaymentInfo(array $order)
    {
        return implode(', ', $order['payment_option'] ?? []);

    }

    /**
     * @param array $order
     * @return array
     */
    public function getProducts(array $order)
    {
        $products = [];
        foreach ($order['products'] as $orderProduct) {
            $products[] = [
                'product_id' =>
                    ($product = $this->productRepository->findByShopProductId($this->shop->_id, (string)$orderProduct['id'])) ?
                        $product->_id : null,
                'quantity' => $orderProduct['quantity'],
                'price' => (float) preg_replace('/\,/', '.',
                    preg_replace('/[^\d^\,]/', '', $orderProduct['price']))
            ];
        }
        return $products;
    }

    /**
     * @param array $order
     * @return string|null
     */
    private function parseDeliveryType(array $order)
    {
        if (!empty($order['delivery_option'])) {
            if ($order['delivery_option']['name'] === self::PICKUP) {
                return AbstractDelivery::TYPE_LOCAL_PICKUP;
            }
            if ($order['delivery_option']['name'] === self::COURIER) {
                return AbstractDelivery::TYPE_COURIER;
            }
            if ($order['delivery_option']['name'] === self::POST) {
                return AbstractDelivery::TYPE_TRANSPORT_COMPANY;
            }

        }
        return NULL;
    }

    /**
     * @param array $order
     * @return int|null
     */
    private function parsePaymentType(array $order)
    {
        if(!empty($order['payment_option'])) {
            if (($order['payment_option']['name'] === self::CASH) ||
            ($order['payment_option']['name'] === self::COD)){
                return OrderRepositoryInterface::PAYMENT_TYPE_COD;
            }
            if (($order['payment_option']['name'] === self::CASHLESS) ||
            ($order['payment_option']['name'] === self::PRIVAT24)){
                return OrderRepositoryInterface::PAYMENT_TYPE_PREPAYMENT;
            }
        }
        return NULL;
    }

    /**
     * @param int $offset
     * @return array
     * @throws importException
     */
    private function getOrders(int $offset) {
        /**
         * @var \Carbon\Carbon $from_date
         */
        try {
            $from_date = ($this->shop->last_order_update) ? $this->shop->last_order_update : $this->shop->created_at;
            $response = $this->integration->order()->getAll($from_date->toIso8601String(), $offset, self::PER_PAGE);
            if (isset($response['orders']) && !empty($response['orders'])) {
                return $response['orders'];
            }
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }
    }
}