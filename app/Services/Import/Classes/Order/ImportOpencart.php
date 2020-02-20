<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 2/13/18
 * Time: 4:23 PM
 */

namespace App\Services\Import\Classes\Order;

use App\Exceptions\importException;
use App\Exceptions\integrationException;
use App\Models\PerAccountsModels\MongoModels\Customer;
use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Models\ImportOrderModel;
use App\Services\Delivery\AbstractDelivery;
use App\Repositories\PerAccountsRepositories\Contracts\OrderRepositoryInterface;
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


class ImportOpencart extends BaseImportClass implements ImportImplementationInterface
{

    const PER_PAGE = 10;

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

    public function prepare($importId)
    {
        parent::prepare($importId);
        $offset = 0;
        while (count($orders = $this->getOrders($offset)) > 0) {
            if (!$this->lastIdFlag) {
                $this->setLastId($orders[0]['order_id']);
            }

            foreach ($orders as $order) {
                if (isset($order['store_url']) && strpos($order['store_url'], 'vr-shop.com.ua') !== false) {
                    /**products
                     * @var $model ImportOrderModel
                     */
                    $model = $this->storage->getItemModel();
                    $model->id = $order['order_id'];
                    $model->order_date = $order['date_added'];
                    $model->comment = $order['comment'];
                    $model->shop_id = $this->shop->_id;
                    $model->customer_id = $this->getCustomer($order)->_id;
                    $model->delivery_info = isset($this->getDeliveryComment($order)[0]) && isset($this->getDeliveryComment($order)[1]) ? $this->getDeliveryComment($order)[0] . ', ' . $this->getDeliveryComment($order)[1] : '';
                    $model->delivery_type = $this->parseDeliveryType($order);
                    $model->delivery_service = $this->getDeliveryService($order);
                    $model->payment_type = $this->parsePaymentType($order);
                    $model->payment_info = $this->getPaymentInfo($order);
                    $model->products = $this->getProducts($order);
                    $model->delivery_cost = $this->getDeliveryCost($order);
                    $model->fields = $this->getDeliveryInfo($order);
                    $this->storage->pushDirty($model);
                }else{
                    /**
                     * @var $model ImportOrderModel
                     */
                    $model = $this->storage->getItemModel();
                    $model->id = $order['order_id'];
                    $model->order_date = $order['date_added'];
                    $model->comment = $order['comment'];
                    $model->shop_id = $this->shop->_id;
                    $model->customer_id = $this->getCustomer($order)->_id;
                    $model->delivery_info = $this->getDeliveryString($order);
                    $model->payment_info = $this->getPaymentInfo($order);
                    $model->products = $this->getProducts($order);
                    $this->storage->pushDirty($model);
                }
            }
            $offset += self::PER_PAGE;
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

    /**
     * @param $order
     * @return Customer
     */
    public function getCustomer($order)
    {
        $phone = formatPhoneNumber($order['telephone']);
        if ($customer = $this->customerRepository->findBy('phone', 'like', $phone)) {
            return $customer;
        } else {
            $data = [
                'name' => $order['firstname'] . ' ' . $order['lastname'],
                'shop_id' => $this->shop->_id,
                'status' => $this->customerRepository::STATUS_ACTIVE,
                'external_id' => ($order['customer_id'] > 0) ? $order['customer_id'] : null,
                'email' => $order['email'],
                'phone' => $phone,
            ];
            return $this->customerRepository->create($data);
        }
    }

    public function getCustomerName($order)
    {
        if (isset($order['store_url']) && strpos($order['store_url'], 'vr-shop.com.ua') !== false) {
            return explode(' ', $order['firstname']);
        }
    }

    public function getDeliveryCost($order)
    {
        if (isset($order['store_url']) && strpos($order['store_url'], 'vr-shop.com.ua') !== false) {
            $products = $this->getProducts($order);
            $price = 0;
            foreach ($products as $product) {
                $price += $product['price'];
            }
            $cost = $order['total'] - $price;
            return $cost;
        } else {
            return 0;
        }
    }

    /**
     * @param $order
     * @return string
     */
    public function getDeliveryComment($order)
    {
        $address = substr($order['shipping_address_1'], strpos($order['shipping_address_1'], ":") + 1);
        $address = str_replace('Дом:', ",", $address);
        $address = str_replace('Время доставки:', ",", $address);
        $address = explode(',', $address);
        return $address;
    }

    public function getDeliveryService($order)
    {
        $service = '';
        if (isset($order['store_url']) && $order['store_url'] == "https://vr-shop.com.ua/") {
            if ($order['shipping_method'] === 'Отделение Новой Почты') {
                $service = 'novaposhta';
            } elseif ($order['shipping_method'] === 'Курьером по Новой почты') {
                $service = 'novaposhta_courier';
            } else {
                $service = '';
            }
        } else {
            $service = '';
        }
        return $service;
    }

    public function getPhoneNumber($order)
    {
        $phone = substr(preg_replace('/^\+?1|\|1|\D/', '', $order['telephone']), 2);
        return $phone;
    }

    public function getDeliveryInfo($order)
    {
        $fields = [];
        try {
            if (isset($order['store_url']) && strpos($order['store_url'], 'vr-shop.com.ua') !== false) {
                if ($order['shipping_method'] === 'Отделение Новой Почты') {
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
                } elseif ($order['shipping_method'] === 'Курьером по Новой почты') {
                    $counterparties = $this->novaposhtaService->counterparty()->recipient()->getAll()->where('CounterpartyType', 'PrivatePerson');
                    $fields =
                        [
                            [
                                "field" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . "_area",
                                "label" => trans('delivery.novaposhta.area'),
                                "type" => Transporter::FIELD_TYPE_SELECT,
                                "required" => true,
                                "values" => $this->transporter->prepareValues($this->novaposhtaService->address()->area()->getAreas(), 'Description', 'Ref'),
                                "value" => null
                            ],
                            [
                                "field" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . "_city",
                                "label" => trans('delivery.novaposhta.city'),
                                "type" => Transporter::FIELD_TYPE_SELECT,
                                "required" => true,
                                "values" => $this->transporter->prepareValues($this->novaposhtaService->address()->city()->getCities(), 'DescriptionRu', 'Ref'),
                                "dependency" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . '_area',
                                "dependency_url" => 'api/novaposhta/address/city/area/%',
                                "value" => null
                            ],
                            [
                                "field" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . "_recipient_counterparty",
                                "label" => trans('delivery.novaposhta.recipient_counterparty'),
                                "type" => Transporter::FIELD_TYPE_RECIPIENT,
                                "required" => true,
                                "dependency" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . '_city',
                                "dependency_url" => 'api/novaposhta/counterparty/recipient/city/%',
                                'value' => $counterparties->first()->Ref,
                                'values' => $this->transporter->prepareValues($counterparties, 'Description', 'Ref'),
                            ],
                            [
                                "field" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . "_recipient_contact",
                                "label" => trans('delivery.novaposhta.recipient_contact'),
                                "type" => Transporter::FIELD_TYPE_CONTACT,
                                "dependency" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . '_recipient_counterparty',
                                "dependency_url" => 'api/novaposhta/counterparty/contact-person/counterparty/%',
                                "required" => true,
                                'value' => $this->getNpRecipientCounterpartyRef($order),
                                'values' => $this->byCounterparty()
                            ],
                            [
                                "field" => Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . "_address",
                                "label" => trans('delivery.novaposhta.address'),
                                "type" => Transporter::FIELD_TYPE_ADDRESS,
                                "required" => true,
                                "values" => $this->transporter->prepareValues($this->novaposhtaService->address()->warehouse()->getWarehouses(), 'DescriptionRu', 'Ref'),
                                "dependency" => [Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . '_city', Transporter::TRANSPORTER_NOVAPOSHTA_COURIER . '_recipient_counterparty'],
                                "dependency_url" => 'api/novaposhta/counterparty/address/city/%/counterparty/%',
                                "value" => null
                            ]
                        ];
                } elseif (strpos($order['shipping_method'], 'Доставка Prime') !== false) {
                    $fields =
                        [
                            [
                                "field" => 'address',
                                "isFetched" => false,
                                "label" => 'Адрес',
                                "required" => true,
                                "type" => 'text',
                                "value" => isset($this->getDeliveryComment($order)[0]) && isset($this->getDeliveryComment($order)[1]) ? $this->getDeliveryComment($order)[0] . ', ' . $this->getDeliveryComment($order)[1] : 'адресс не указан',
                                "values" => []
                            ],
                            [
                                "field" => 'name',
                                "isFetched" => false,
                                "label" => 'Имя',
                                "required" => true,
                                "type" => 'text',
                                "value" => isset($order['firstname']) && $order['firstname'] != '' ? $order['firstname'] : $this->getCustomer($order)->name,
                                "values" => []
                            ],
                            [
                                "field" => 'phone',
                                "isFetched" => false,
                                "label" => 'Телефон',
                                "required" => true,
                                "type" => 'phone',
                                "value" => isset($order['telephone']) && $order['telephone'] != '' ? $this->getPhoneNumber($order) : $this->getCustomer($order)->phone,
                                "values" => []
                            ]
                        ];
                }
            }
        } catch (\Exception $exception) {
            \Log::error('Error parsing glubokaya integraciya vr-shop: '.$exception->getMessage(), $exception->getTrace());
        }
        return $fields;
    }

    public function getDeliveryString($order)
    {
        return implode(', ', [
            $order['shipping_method'],
            $order['shipping_firstname'],
            $order['shipping_lastname'],
            $order['shipping_country'],
            $order['shipping_zone'],
            $order['shipping_city'],
            $order['shipping_address_1'],
            $order['shipping_address_2'],
            $order['shipping_postcode'],
        ]);
    }

    public function getDeliveryCity($order)
    {
        $cities = $this->novaposhtaService->address()->city()->getCities();
        $city = $cities->where('DescriptionRu', $order['shipping_city'])->first();
        return $city;
    }

    public function getWarehouse($order, $city)
    {

        $department = str_replace('№ ', '№', str_replace(':', '', rtrim(strstr($order['shipping_address_1'], 'Отделение №'))));
        if ($department != '') {
            $department = explode(' ', $department);
            $department = str_replace('№', '', $department[1]);
        } else {
            $department = 1;
        }

        $warehouses = $this->novaposhtaService->address()->warehouse()->getWarehousesByCity($city->Ref);
        $warehouse = $warehouses->where('Number', '=', $department)->first();
        return $warehouse;
    }

    public function getDeliveryArea($order)
    {
        $cities = $this->novaposhtaService->address()->city()->getCities();
        $city = $cities->where('DescriptionRu', $order['shipping_city'])->first();
        return $city;
    }

    public function getNpRecipientCounterparty()
    {
        $recipient = $this->novaposhtaService->counterparty()->recipient()->getAll()->where('CounterpartyType', 'PrivatePerson')->first();
        return $this->novaposhtaService->counterparty()->contactPerson()->getByCounterparty($recipient->Ref);
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

    public function getNpRecipientCounterpartyRef($order)
    {
        $recipient = $this->novaposhtaService->counterparty()->recipient()->getAll()->where('CounterpartyType', 'PrivatePerson')->first();
        $contacts = $this->novaposhtaService->counterparty()->contactPerson()->getByCounterparty($recipient->Ref);
        $contact = $contacts->where('Phones', substr(formatPhoneNumber($order['telephone']), 1))->first();
        if (is_null($contact)) {
            $full_name = $this->getCustomerName($order);
            $contact = $this->novaposhtaService->counterparty()->contactPerson()->create(
                [
                    'CounterpartyRef' => $recipient->Ref,
                    'FirstName' => isset($full_name[1]) && $full_name[1] != '' ? $full_name[1] : 'нет имени',
                    'LastName' => isset($full_name[0]) && $full_name[0] != '' ? $full_name[0] : 'нет фамилии',
                    'MiddleName' => isset($full_name[2]) && $full_name[2] != '' ? $full_name[2] : '',
                    'Phone' => isset($order['telephone']) ? $this->getPhoneNumber($order) : '',
                ]
            );
            return $contact->Ref;
        } else {
            return $contact->Ref;
        }
    }

    /**
     * @param array $order
     * @return string|null
     */
    private function parseDeliveryType(array $order)
    {

        if (!empty($order['shipping_method'])) {
            if ($order['shipping_method'] === 'Курьером по Новой почты') {
                return AbstractDelivery::TYPE_TRANSPORT_COMPANY;
            }
            if ($order['shipping_method'] === 'Отделение Новой Почты') {
                return AbstractDelivery::TYPE_TRANSPORT_COMPANY;
            }
            if (strpos($order['shipping_method'], 'Доставка Prime') !== false) {
                return AbstractDelivery::TYPE_COURIER;
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
        if (!empty($order['payment_method'])) {
            if ($order['payment_method'] === 'Наличными при получении') {
                return OrderRepositoryInterface::PAYMENT_TYPE_COD;
            }
        }
        return NULL;
    }

    /**
     * @param $order
     * @return string
     */
    public function getPaymentInfo($order)
    {
        return implode(', ', [
            $order['payment_method'],
            $order['payment_firstname'],
            $order['payment_lastname'],
        ]);
    }

    /**
     * @param $order
     * @return array
     */
    public function getProducts($order)
    {
        $products = [];
        foreach ($order['products'] as $orderProduct) {
            $product = $this->productRepository->findByShopProductId($this->shop->_id, (string)$orderProduct['product_id']);

            if ($product) {
                if (!empty($orderProduct['options'])){
                    $orderOptions = array_map(function ($ar) {
                        return [
                            'option_id' => $ar['option_id'],
                            'option_value_id' => $ar['option_value_id']
                        ];
                    }, $orderProduct['options']);

                    $embedProduct = $this->productRepository->findProductByOrderOptions($product, $this->shop->_id, $orderOptions);

                    if ($embedProduct != false){
                        $products[] = [
                            'product_id' => $embedProduct->_id,
                            'quantity' => $orderProduct['quantity'],
                            'price' => $orderProduct['price'],
                        ];
                    }
                }else{
                    $products[] = [
                        'product_id' => $product->_id,
                        'quantity' => $orderProduct['quantity'],
                        'price' => $orderProduct['price'],
                    ];
                }
            }
        }
        return $products;
    }

    /**
     * @param int $offset
     * @return array
     * @throws importException
     */
    private function getOrders($offset)
    {
        try {
            $from_date = ($this->shop->last_order_update) ? $this->shop->last_order_update : $this->shop->created_at;
            $response = $this->integration->order()->getAll((string)$from_date, $offset, self::PER_PAGE);
            if (isset($response['orders'])) {
                return $response['orders'];
            }
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }
    }

}