<?php

namespace App\Services\Import\Classes\Order;

use App\Exceptions\importException;
use App\Exceptions\integrationException;
use App\Repositories\PerAccountsRepositories\Contracts\OrderRepositoryInterface;
use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Models\ImportOrderModel;
use Carbon\Carbon;
use App\Services\Delivery\AbstractDelivery;
use App\Services\Novaposhta\Contracts\NovaposhtaServiceInterface;
use App\Services\Delivery\Methods\Transporter;
use App\Repositories\PerAccountsRepositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ImportRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use App\Services\Import\Contracts\StorageRepositoryInterface;
use App\Services\Integration\Contracts\IntegrationServiceInterface;
use App\Services\Novaposhta\Counterparty\Contracts\NovaposhtaCounterpartySenderServiceInterface;
use App\Repositories\Novaposhta\Counterparty\Contracts\NovaposhtaContactPersonRepositoryInterface;

class ImportRozetka extends BaseImportClass implements ImportImplementationInterface
{
    const PER_PAGE = 20;
    const DELIVERY_TYPE_PICKUP = 1;  //новая почта самовывоз в розетеке это доставка на отделение
    const DELIVERY_TYPE_COURIER = 2; //новая почта курьер

    private $lastIdFlag = false;


    /**
     * @var \App\Services\Novaposhta\NovaposhtaService
     */
    public $novaposhtaService;


    /**
     * @var Transporter
     */
    public $transporter;

    /**
     * @var \App\Services\Novaposhta\Counterparty\NovaposhtaCounterpartyService
     */
    public $counterpartyService;


    /**
     * @var \App\Services\Novaposhta\Counterparty\NovaposhtaCounterpartyContactPersonService
     */
    private $counterpartyContactPersonService;

    /**
     * ImportOpencart constructor.
     * @param NovaposhtaContactPersonRepositoryInterface $counterpartyContactPersonService
     * @param NovaposhtaCounterpartySenderServiceInterface $counterpartyService
     * @param StorageRepositoryInterface $storage
     * @param ImportRepositoryInterface $importRepository
     * @param IntegrationServiceInterface $integrationService
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param NovaposhtaServiceInterface $novaposhtaService
     * @param Transporter $transporter
     */
    public function __construct(
        NovaposhtaContactPersonRepositoryInterface $counterpartyContactPersonService,
        NovaposhtaCounterpartySenderServiceInterface $counterpartyService,
        StorageRepositoryInterface $storage,
        ImportRepositoryInterface $importRepository,
        IntegrationServiceInterface $integrationService,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        NovaposhtaServiceInterface $novaposhtaService,
        Transporter $transporter
    )
    {
        parent::__construct($storage, $importRepository, $integrationService, $categoryRepository, $productRepository, $customerRepository);
        $this->counterpartyContactPersonService = $counterpartyContactPersonService;
        $this->counterpartyService = $counterpartyService;
        $this->novaposhtaService = $novaposhtaService;
        $this->transporter = $transporter;
    }

    /**
     * @param $importId
     * @return mixed|void
     * @throws importException
     */
    public function prepare($importId)
    {
        parent::prepare($importId);
        $page = 1;

//        while (count($orders = $this->getOrders($page)) > 0) {
        $orders = $this->getOrders($page);
        while ($orders['_meta']['pageCount'] >= $page) {
            if (!$this->lastIdFlag) {
                $this->setLastId($orders['orders'][0]['id']);
            }

            foreach ($orders['orders'] as $order) {
                $orderInfo = $this->getOrder($order);
                /**
                 * @var $model ImportOrderModel
                 */
                $model = $this->storage->getItemModel();
                $model->id = $order['id'];
                $model->order_date = (string)(Carbon::parse($order['created']));
                $model->shop_id = $this->shop->_id;
                $model->customer_id = $this->getCustomer($orderInfo)->_id;
                $model->payment_type = !is_null($this->parsePaymentType($orderInfo)) ? $this->parsePaymentType($orderInfo) : '';
                $model->payment_info = $this->getPaymentInfo($orderInfo);
                $model->comment = $orderInfo['comment'] != '' ? $orderInfo['comment'] : '';
                $model->products = $this->getProducts($orderInfo);
                $model->delivery_type = $this->parseDeliveryType($orderInfo);
                $model->delivery_info = $this->getDeliveryComment($orderInfo);
                $model->delivery_service = $this->getDeliveryService($orderInfo);
                $model->delivery_cost = 0;
                $model->fields = $this->getDeliveryInfo($orderInfo);
                $this->storage->pushDirty($model);
            }
            $page += self::PER_PAGE;
        }
    }

    private function setLastId($id)
    {
        if ($this->lastIdFlag) {
            return;
        }
        $this->shop->last_order_id = $id;
        $this->shop->save();
        $this->lastIdFlag = true;
    }

    /*
     * @param $order
     * @return Customer
     */
    public function getCustomer($order)
    {
        $phone = formatPhoneNumber($order['user_phone']);
        if ($customer = $this->customerRepository->findBy('phone', 'like', $phone)) {
            return $customer;
        } else {
            $name = $this->getUserName($order);
            $data = [
                'name' => $name,
                'shop_id' => $this->shop->_id,
                'status' => $this->customerRepository::STATUS_ACTIVE,
                'external_id' => null,
                'email' => $order['user']['email'],
                'phone' => $phone,
            ];
            return $this->customerRepository->create($data);
        }
    }

    /**
     * @param $order
     * @return string
     */
    public function getUserName($order)
    {
        switch ($order) {
            case isset($order['user']['contact_fio']) && ($order['user']['contact_fio'] != '' || $order['user']['contact_fio'] != null):
                $name = $order['user']['contact_fio'];
                break;
            case isset($order['delivery']['recipient_title']) && ($order['delivery']['recipient_title'] != '' || $order['delivery']['recipient_title'] != null) :
                $name = $order['delivery']['recipient_title'];
                break;
            default:
                $name = 'Нет имени';
                break;
        }
        return $name;
    }

    /**
     * @param $order
     * @return string
     */
    public function getDeliveryComment($order)
    {
        if (!empty($order['delivery'])) {
            return implode(', ', [
                isset($order['delivery']['delivery_service_name']) ? $order['delivery']['delivery_service_name'] : '',
                isset($order['delivery']['recipient_title']) ? $order['delivery']['recipient_title'] : '',
                isset($order['delivery']['city']['name']) ? $order['delivery']['city']['name'] : '',
                isset($order['delivery']['place_street']) ? $order['delivery']['place_street'] : '',
                isset($order['delivery']['place_street']) ? $order['delivery']['place_street'] : '',
                isset($order['delivery']['place_house']) ? $order['delivery']['place_house'] : '',
            ]);
        } else {
            return '';
        }
    }

    /**
     * @param $order
     * @return string
     */
    public function getPaymentInfo($order)
    {
        if (!empty($order['credit_info'])) {
            return implode(', ',
                $order['credit_info']
            );
        } else {
            return '';
        }
    }

    /**
     * @param $order
     * @return array
     */
    public function getProducts($order)
    {
        $products = [];
//        if (!empty($order['purchases'][0]['conf_details']['goods'])){
//            $goods = $order['purchases'][0]['conf_details']['goods'];
//            foreach ($goods as $orderProduct) {
//
//                https://api.seller.rozetka.com.ua/items/search
//                $product = $this->productRepository->findByShopProductId($this->shop->_id, (string)$orderProduct->product_id);
//
//                if ($product) {
//                    $products[] = [
//                        'product_id' => $product->_id,
//                        'quantity' => $orderProduct->quantity,
//                        'price' => $orderProduct->price,
//                    ];
//                }
//
//            }
//        }
        return $products;
    }

    /**
     * @param $page
     * @return mixed
     * @throws importException
     */
    private function getOrders($page)
    {
        try {
            $from_date = ($this->shop->last_order_update) ? $this->shop->last_order_update : $this->shop->created_at;
//            $response = $this->integration->order()->getAll((string)$from_date, $page, self::PER_PAGE);
            $response = $this->integration->order()->hasNew();
            dd($response);

            if (isset($response['content']['orders']) && $response['content']['_meta']['pageCount'] <= $page) {
                return $response['content'];
            }
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }
    }

    /**
     * @param array $order
     * @return int|null
     */
    private function parsePaymentType(array $order)
    {
        if (!empty($order['payment_type'])) {
            if ($order['payment_type'] === 'cash') {
                return OrderRepositoryInterface::PAYMENT_TYPE_COD;
            }
        }
        return null;
    }

    /**
     * @param $order
     * @return mixed
     * @throws importException
     */
    public function getOrder($order)
    {
        try {
            $response = $this->integration->order()->getOrder($order['id']);
            if (isset($response['content'])) {
                return $response['content'];
            }
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }
    }


    /**
     * @param $order
     * @return string
     */
    public function getDeliveryService($order)
    {
        $service = '';
        if (isset($order['delivery'])) {
            if ($order['delivery']['delivery_service_name'] === 'Новая Почта' && $order['delivery']['delivery_method_id'] == self::DELIVERY_TYPE_PICKUP) {
                $service = 'novaposhta';
            } elseif ($order['delivery']['delivery_service_name'] === 'Новая Почта' && $order['delivery']['delivery_method_id'] == self::DELIVERY_TYPE_COURIER) {
                $service = 'novaposhta_courier';
            } else {
                $service = '';
            }
        }
        return $service;
    }


    public function getDeliveryInfo($order)
    {
        if ($order['delivery']['delivery_service_name'] === 'Новая Почта' && $order['delivery']['delivery_method_id'] == self::DELIVERY_TYPE_PICKUP) {
            $city = $this->getDeliveryCity($order);
            $warehouse = $this->getWarehouse($order, $city);
            $counterparties = $this->novaposhtaService->counterparty()->recipient()->getAll()->where('CounterpartyType', 'PrivatePerson');
            $fields =
                [
                    [
                        "field" => 'novaposhta_area',
                        "flag" => false,
                        "isFetched" => 'Адрес',
                        "label" => true,
                        "required" => true,
                        "type" => 'select',
                        "value" => !is_null($city) ? $city->Area : null,
                        'values' => $this->transporter->prepareValues($this->novaposhtaService->address()->city()->getCities(), 'DescriptionRu', 'Ref'),
                    ],
                    [
                        "field" => 'novaposhta_city',
                        "dependency" => 'novaposhta_area',
                        "dependency_url" => "api/novaposhta/address/city/area/%",
                        "flag" => "novaposhta",
                        "isFetched" => true,
                        "label" => "Город",
                        "required" => true,
                        "type" => "select",
                        "value" => !is_null($city) ? $city->Ref : null,
                        'values' => $this->transporter->prepareValues($this->novaposhtaService->address()->city()->getCities(), 'DescriptionRu', 'Ref'),
                    ],
                    [
                        "field" => 'novaposhta_recipient_counterparty',
                        "dependency" => 'novaposhta_city',
                        "dependency_url" => 'api/novaposhta/counterparty/recipient/city/%',
                        "flag" => 'novaposhta',
                        "isFetched" => true,
                        "label" => 'Получатель',
                        "required" => true,
                        "type" => 'recipient',
                        'value' => $counterparties->first()->Ref,
                        'values' => $this->transporter->prepareValues($counterparties, 'Description', 'Ref'),
                    ],
                    [
                        "field" => 'novaposhta_recipient_contact',
                        "dependency" => 'novaposhta_recipient_counterparty',
                        "dependency_url" => 'api/novaposhta/counterparty/contact-person/counterparty/%',
                        "flag" => 'novaposhta',
                        "isFetched" => true,
                        "label" => 'Контактное лицо',
                        "required" => true,
                        "type" => 'contact',
                        'value' => $this->getNpRecipientCounterpartyRef($order),
                        'values' => $this->byCounterparty()
                    ],
                    [
                        "field" => 'novaposhta_warehouse',
                        "dependency" => 'api/novaposhta/address/warehouse/city/%',
                        "dependency_url" => false,
                        "flag" => 'novaposhta',
                        "isFetched" => true,
                        "label" => 'Отделение',
                        "required" => true,
                        "type" => 'select',
                        'value' => !is_null($warehouse) ? $warehouse->Ref : null,
                        'values' => $this->transporter->prepareValues($this->novaposhtaService->address()->warehouse()->getWarehouses(), 'DescriptionRu', 'Ref'),
                    ]
                ];
        }
//        if ($order['delivery']['delivery_service_name'] === 'Новая Почта' && $order['delivery']['delivery_method_id'] == 2) {
//            $this->getNpRecipientCounterpartyRef($order);
//            $counterparties = $this->novaposhtaService->counterparty()->recipient()->getAll()->where('CounterpartyType', 'PrivatePerson');
//            $fields =
//                [
//                    [
//                        "field" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . "_area",
//                        "label" => trans('delivery.novaposhta.area'),
//                        "type" => Transporter::FIELD_TYPE_SELECT,
//                        "required" => true,
//                        "values" => $this->transporter->prepareValues($this->novaposhtaService->address()->area()->getAreas(), 'Description', 'Ref'),
//                        "value" => null
//                    ],
//                    [
//                        "field" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . "_city",
//                        "label" => trans('delivery.novaposhta.city'),
//                        "type" => Transporter::FIELD_TYPE_SELECT,
//                        "required" => true,
//                        "values" => $this->transporter->prepareValues($this->novaposhtaService->address()->city()->getCities(), 'DescriptionRu', 'Ref'),
//                        "dependency" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . '_area',
//                        "dependency_url" => 'api/novaposhta/address/city/area/%',
//                        "value" => null
//                    ],
//                    [
//                        "field" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . "_recipient_counterparty",
//                        "label" => trans('delivery.novaposhta.recipient_counterparty'),
//                        "type" => Transporter::FIELD_TYPE_RECIPIENT,
//                        "required" => true,
//                        "dependency" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . '_city',
//                        "dependency_url" => 'api/novaposhta/counterparty/recipient/city/%',
//                        'value' => $counterparties->first()->Ref,
//                        'values' => $this->transporter->prepareValues($counterparties, 'Description', 'Ref'),
//                    ],
//                    [
//                        "field" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . "_recipient_contact",
//                        "label" => trans('delivery.novaposhta.recipient_contact'),
//                        "type" => Transporter::FIELD_TYPE_CONTACT,
//                        "dependency" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . '_recipient_counterparty',
//                        "dependency_url" => 'api/novaposhta/counterparty/contact-person/counterparty/%',
//                        "required" => true,
//                        'value' => $this->getNpRecipientCounterpartyRef($order),
//                        'values' => $this->byCounterparty()
//                    ],
//                    [
//                        "field" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . "_address",
//                        "label" => trans('delivery.novaposhta.address'),
//                        "type" => Transporter::FIELD_TYPE_ADDRESS,
//                        "required" => true,
//                        "values" => $this->transporter->prepareValues($this->novaposhtaService->address()->warehouse()->getWarehouses(), 'DescriptionRu', 'Ref'),
//                        "dependency" => [Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . '_city', Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . '_recipient_counterparty'],
//                        "dependency_url" => 'api/novaposhta/counterparty/address/city/%/counterparty/%',
//                        "value" => null
//                    ]
//                ];
//        }
        else {
            $fields = [];
        }
        return $fields;
    }

    public function getNpRecipientCounterpartyRef($order)
    {
        $recipient = $this->novaposhtaService->counterparty()->recipient()->getAll()->where('CounterpartyType', 'PrivatePerson')->first();
        $contacts = $this->novaposhtaService->counterparty()->contactPerson()->getByCounterparty($recipient->Ref);
        $contact = $contacts->where('Phones', substr(formatPhoneNumber($order['user_phone']), 1))->first();
        if (is_null($contact)) {
            $full_name = $this->getCustomerName($order);
            $contact = $this->novaposhtaService->counterparty()->contactPerson()->create(
                [
                    'CounterpartyRef' => $recipient->Ref,
                    'FirstName' => isset($full_name[1]) && $full_name[1] != '' ? $full_name[1] : 'нет имени',
                    'LastName' => isset($full_name[0]) && $full_name[0] != '' ? $full_name[0] : 'нет фамилии',
                    'MiddleName' => isset($full_name[2]) && $full_name[2] != '' ? $full_name[2] : '',
                    'Phone' => isset($order['user_phone']) ? $this->getPhoneNumber($order) : '',
                ]
            );
            return $contact->Ref;
        } else {
            return $contact->Ref;
        }
    }

    public function getPhoneNumber($order)
    {
        $phone = substr(preg_replace('/^\+?1|\|1|\D/', '', $order['user_phone']), 2);
        return $phone;
    }

    public function getCustomerName($order)
    {
        return explode(' ', $order['delivery']['recipient_title']);
    }

    public function byCounterparty()
    {
        $recipient = $this->novaposhtaService->counterparty()->recipient()->getAll()->where('CounterpartyType', 'PrivatePerson')->first();
        $values = $this->novaposhtaService->counterparty()->contactPerson()->getByCounterparty($recipient->Ref);
        $data = [];
        foreach ($values as $value) {
            $data[] = [
                'label' => ($value->Phones) ? $value->Description . ' ( ' . $value->Phones . ' )' : $value->Description,
                'value' => $value->Ref
            ];
        }
        return $data;
    }


    public function getDeliveryCity($order)
    {
        $cities = $this->novaposhtaService->address()->city()->getCities();
        $city = $cities->where('DescriptionRu', $order['delivery']['city']['name'])->first();
        return $city;
    }

    /**
     * @param $order
     * @param $city
     * @return mixed|null
     */
    public function getWarehouse($order, $city)
    {

        if (isset($order['delivery']['place_number']) && $order['delivery']['place_number'] != '') {
            $department = $order['delivery']['place_number'];
        } else {
            $department = 1;
        }

        if (isset($city->Ref)) {
            $warehouses = $this->novaposhtaService->address()->warehouse()->getWarehousesByCity($city->Ref);
            $warehouse = $warehouses->where('Number', '=', $department)->first();
            return $warehouse;
        }
        return null;
    }

    public function getDeliveryArea($order)
    {
        $cities = $this->novaposhtaService->address()->city()->getCities();
        $city = $cities->where('DescriptionRu', $order['shipping_city'])->first();
        return $city;
    }


    /**
     * @param array $order
     * @return string|null
     */
    private function parseDeliveryType(array $order)
    {
        if (!empty($order['delivery'])) {
            if ($order['delivery']['delivery_service_name'] === 'Новая Почта') {
                return AbstractDelivery::TYPE_TRANSPORT_COMPANY;
            }
//            if ($order['shipping_method'] === 'Отделение Новой Почты') {
//                return AbstractDelivery::TYPE_TRANSPORT_COMPANY;
//            }
//            if (strpos($order['shipping_method'], 'Доставка Prime') !== false) {
//                return AbstractDelivery::TYPE_COURIER;
//            }
        }
        return NULL;
    }

}
