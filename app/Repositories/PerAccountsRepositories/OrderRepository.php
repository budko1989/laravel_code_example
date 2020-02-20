<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 12/18/17
 * Time: 12:05 PM
 */

namespace App\Repositories\PerAccountsRepositories;

use App\Exceptions\boundException;
use App\Models\PerAccountsModels\MongoModels\Order;
use App\Repositories\PerAccountsRepositories\Contracts\OrderRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\TrafficLightRepositoryInterface;
use App\Repositories\Repository;
use App\Services\Delivery\AbstractDelivery;
use App\Traits\ColorTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use MongoDB\Collection;

class OrderRepository extends Repository implements OrderRepositoryInterface
{
    use ColorTrait;

    /**
     * @var TrafficLightRepository
     */
    private $trafficLightRepository;

    public function __construct(TrafficLightRepositoryInterface $trafficLightRepository)
    {
        $this->trafficLightRepository = $trafficLightRepository;
    }

    /**
     * @var string|self
     */
    protected $modelClassName = 'App\Models\PerAccountsModels\MongoModels\Order';

    /**
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_NEW,
            self::STATUS_PROGRESS,
            self::STATUS_SHIPMENT,
            self::STATUS_FORMATION,
            self::STATUS_SHIPPED,
            self::STATUS_DELIVERED,
            self::STATUS_FINISHED,
            self::STATUS_DEFERRED,
            self::STATUS_CANCELED,
            self::STATUS_DELETED
        ];
    }

//    /**
//     * @return array
//     */
//    public static function getDeliveryTypes()
//    {
//        return [
//            self::DELIVERY_TYPE_TRANSPORT_COMPANY,
//            self::DELIVERY_TYPE_COURIER,
//            self::DELIVERY_TYPE_LOCAL_PICKUP
//        ];
//    }

    /**
     * @return array
     */
    public static function getPaymentStatuses()
    {
        return [
            self::PAYMENT_STATUS_NOT_PAID,
            self::PAYMENT_STATUS_PAID
        ];
    }

    /**
     * @return array
     */
    public static function getPaymentTypes()
    {
        return [
            self::PAYMENT_TYPE_COD,
            self::PAYMENT_TYPE_PREPAYMENT,
        ];
    }

    /**
     * @param Model $order
     * @param int $statusId
     * @return void
     */
    public function setStatus(Model $order, int $statusId)
    {
        /**
         * @var $order Order
         */
        $order->status_id = $statusId;
        $order->save();
    }

    /**
     * @param string $id
     * @return \Illuminate\Database\Eloquent\Collection|Model|null|static|static[]
     */
    public function findOneWithCustomersAndProducts(string $id)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $query = $model::with('customer.shop', 'products', 'declarations', 'history', 'source', 'currency', 'shipments');
        if (!is_null(\Auth::user()->shop_ids)) {
            $query->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }

        $order = $query->find($id);
        foreach ($order->products as $product) {
            $product->shipmentSource;
            $product->shipment;
        }

        return $order;
    }

    /**
     * @param string $shopId
     * @param string $externalId
     * @return \Illuminate\Database\Eloquent\Collection|Model|null|static|static[]
     */
    public function findOneByExternalId(string $shopId, string $externalId)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $query = $model::with('customer', 'products.product');
        if (!is_null(\Auth::user()->shop_ids)) {
            $query->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        $query->where('shop_id', $shopId);
        $query->where('external_id', $externalId);
        return $query->first();
    }

    /**
     * @param int|null $statusId
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findAllWithCustomersAndProducts(int $statusId = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $query = $model::with('customer', 'products');
        if ($statusId) {
            $query->where('status_id', (int)$statusId);
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $query->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $query->orderBy('order_date')->get();
    }

    /**
     * @param string $customerId
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findByCustomerWithCustomersAndProducts(string $customerId)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $query = $model::with('customer', 'products');
        $query->where('customer_id', (string)$customerId);
        if (!is_null(\Auth::user()->shop_ids)) {
            $query->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $query->get();
    }

    /**
     * @param $num string
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findAllByNum(string $num)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $query = $model::with([]);
        $query->where('order_num', 'like', '%' . (string)$num . '%');
        if (!is_null(\Auth::user()->shop_ids)) {
            $query->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        $query->limit(10);
        return $query->get();
    }

    /**
     * @param $statusId int
     * @param $dateFrom string
     * @param $dateTo string
     * @param $payed bool
     * @return integer
     */
    public function countByStatus(int $statusId = null, string $dateFrom = null, string $dateTo = null, bool $payed = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        if ($statusId) {
            $model->where('status_id', $statusId);
        }
        if ($payed) {
            $model->where('payment_status_id', $payed);
        }
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model->count();
    }

    /**
     * @param $statusId int
     * @param $dateFrom string
     * @param $dateTo string
     * @param $payed int
     * @return integer
     */
    public function sumByStatus(int $statusId = null, string $dateFrom = null, string $dateTo = null, int $payed = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();

        if ($statusId) {
            $model->where('status_id', (int)$statusId);
        }
        if ($payed) {
            $model->where('payment_status_id', $payed);
        }
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model->sum('sum_amount');
    }

    /**
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @param null|int $payed
     * @return float|int
     */
    public function averageCheck(string $dateFrom = null, string $dateTo = null, int $payed = null)
    {
        $c = $this->countByStatus(null, $dateFrom, $dateTo);
        return ($c == 0) ? 0 : $this->sumByStatus(null, $dateFrom, $dateTo, $payed) / $c;
    }

    /**
     * @param $shopId string
     * @param $dateFrom string
     * @param $dateTo string
     * @return integer
     */
    public function countByShop(string $shopId, string $dateFrom = null, string $dateTo = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        $model->where('shop_id', $shopId);
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model->count();
    }

    /**
     * @param $delivery integer
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByDelivery(int $delivery, string $dateFrom = null, string $dateTo = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        $model->where('delivery.delivery_type', $delivery);
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model->count();
    }

    /**
     * @param $transporter integer
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByDeliveryTransporter(int $transporter, string $dateFrom = null, string $dateTo = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        $model->where('delivery.delivery_type', AbstractDelivery::TYPE_TRANSPORT_COMPANY);
        $model->where('delivery.delivery_service', $transporter);
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model->count();
    }

    /**
     * @param $payment integer
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByPayment(int $payment, string $dateFrom = null, string $dateTo = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        $model->where('payment_type', $payment);
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model->count();
    }

    /**
     * @param $status integer
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByPaymentStatus(int $status, string $dateFrom = null, string $dateTo = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        $model->where('payment_status_id', $status);
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model->count();
    }

    /**
     * @param $area string
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByNPArea(string $area, string $dateFrom = null, string $dateTo = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        $model->where('delivery.transporter_novaposhta_area.value', $area);
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model->count();
    }

    /**
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return mixed
     */
    public function countProducts(string $dateFrom = null, string $dateTo = null)
    {
        $model = $this->modelClassName;
        $shops = json_decode(\Auth::user()->shop_ids);
        return $model::raw(function ($collection) use ($dateFrom, $dateTo, $shops) {
            $query = [
//                [
//                    '$match' => [
//                        'order_date' => [
//                            '$gte' => 'ISODate("2018-06-17T00:00:00.000Z")',
//                            '$lt'  => 'ISODate("2018-06-19T00:00:00.000Z")',
//                        ]
//                    ],
//                ],
                [
                    '$group' => [
                        '_id' => null,
                        'cc' => [
                            '$sum' => [
                                '$size' => [
                                    '$ifNull' => [
                                        '$products',
                                        []
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            if (!is_null(\Auth::user()->shop_ids)) {
                array_push($query,
                    ['$in' => $shops]);
            }

            return $collection->aggregate($query);
        });
    }

    /**
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return mixed
     */
    public function getOrdersByHours(string $dateFrom = null, string $dateTo = null)
    {
        $model = $this->modelClassName;
        $shops = json_decode(\Auth::user()->shop_ids);
        return $model::raw(function ($collection) use ($dateFrom, $dateTo, $shops) {
            $query = [
//                [
//                    '$match' => [
//                        'order_date' => [
//                            '$gte' => 'ISODate("2018-06-17T00:00:00.000Z")',
//                            '$lt'  => 'ISODate("2018-06-19T00:00:00.000Z")',
//                        ]
//                    ],
//                ],
                [
                    '$group' => [
                        '_id' => [
                            '$hour' => '$order_date'
                        ],
                        'total' => [
                            '$sum' => 1
                        ]
                    ]
                ]
            ];
            if (!is_null(\Auth::user()->shop_ids)) {
                array_push($query,
                    ['$in' => $shops]);
            }
            return $collection->aggregate($query);
        });
    }

    /**
     * @param null|int $status_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return mixed
     */
    public function countOverdueOrdersByUsers(int $status_id = null, string $dateFrom = null, string $dateTo = null)
    {
        $model = $this->modelClassName;
        $shops = json_decode(\Auth::user()->shop_ids);
        return $model::raw(function ($collection) use ($dateFrom, $dateTo, $shops) {
            $query = [
                '$match' => [
                    'color' => 'red'
//                        'order_date' => [
//                            '$gte' => 'ISODate("2018-06-17T00:00:00.000Z")',
//                            '$lt'  => 'ISODate("2018-06-19T00:00:00.000Z")',
//                        ]
                ],
                [
                    '$group' => [
                        '_id' => '$user_id',
                        'total' => [
                            '$sum' => 1
                        ]
                    ]
                ]
            ];
            if (!is_null(\Auth::user()->shop_ids)) {
                array_push($query,
                    ['$in' => $shops]);
            }

            return $collection->aggregate($query);
        });
    }

    /**
     * @param null|int $status_id
     * @param null|int $user_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return int
     */
    public function countGreen(int $status_id = null, int $user_id = null, string $dateFrom = null, string $dateTo = null)
    {

        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        $model->where('color', 'green');
        if ($status_id) {
            $model->where('status_id', $status_id);
        }
        if ($user_id) {
            $model->where('user_id', $user_id);
        }
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model->count();

    }

    /**
     * @param null|int $status_id
     * @param null|int $user_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return int
     */
    public function countRed(int $status_id = null, int $user_id = null, string $dateFrom = null, string $dateTo = null)
    {

        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        $model->where('color', 'red');
        if ($status_id) {
            $model->where('status_id', $status_id);
        }
        if ($user_id) {
            $model->where('user_id', $user_id);
        }
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model->count();

    }

    /**
     * @param int $status_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return int
     */
    public function countOverdue(int $status_id, string $dateFrom = null, string $dateTo = null)
    {
        return $this->overdue($status_id, $dateFrom, $dateTo)->count();
    }

    /**
     * @return int
     */
    public function countAllInAccountByCurrentMonth()
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();

        $model->where('color', 'green');
        $model->where('order_date', '>=', Carbon::now()->startOfMonth());
        $model->where('order_date', '<=', Carbon::now());

        return $model->count();

    }

    /**
     * @param int $status_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return int|mixed
     * @throws \Exception
     */
    public function avgTime(int $status_id, string $dateFrom = null, string $dateTo = null)
    {
        $field = $this->getOverdueField($status_id);
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        if ($status_id) {
            $model->where('status_id', $status_id);
        }
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        $res = $model->avg($field);
        return ($res) ? $res : 0;
    }

    /**
     * @param int $status_id
     * @return string
     * @throws \Exception
     */
    private function getOverdueField(int $status_id)
    {
        switch ($status_id) {
            case self::STATUS_NEW:
                return 'time_in_new';

            case self::STATUS_PROGRESS:
                return 'time_in_progress';

            case self::STATUS_SHIPMENT:
                return 'time_in_shipment';

            case self::STATUS_SHIPPED:
                return 'time_in_shipped';

            case self::STATUS_DELIVERED:
                return 'time_in_delivered';

            default:
                throw new \Exception('status not supported');
        }
    }

    /**
     * @param int $status_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return Order|\Illuminate\Database\Eloquent\Builder|int
     */
    private function overdue(int $status_id, string $dateFrom = null, string $dateTo = null)
    {
        /**
         * @var $model Order
         * @var $tl \App\Models\PerAccountsModels\MongoModels\Settings\TrafficLight
         */
        $model = $this->modelClassName;
        $model = $model::query();
        $tl = $this->trafficLightRepository->findByTypeAndStatus(TrafficLightRepositoryInterface::TYPE_ORDER, $status_id);
        if (!$tl) return 0;
        try {
            $model->where($this->getOverdueField($status_id), '>', $tl->max * 60 * 60);
        } catch (\Exception $e) {
            return 0;
        }
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        return $model;
    }

    /**
     * @param null|int $status_id
     * @param null|int $user_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return int|mixed
     */
    public function avgExecuteTime(int $status_id = null, int $user_id = null, string $dateFrom = null, string $dateTo = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        $model->where('execute_time', 'exists', true);
        if ($status_id) {
            $model->where('status_id', $status_id);
        }
        if ($user_id) {
            $model->where('user_id', $user_id);
        }
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        $res = $model->avg('execute_time');
        return ($res) ? $res : 0;
    }

    /**
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return float|int
     */
    public function avgProducts(string $dateFrom = null, string $dateTo = null)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        if ($dateFrom) {
            $model->where('order_date', '>=', new Carbon($dateFrom));
        }
        if ($dateTo) {
            $model->where('order_date', '<=', new Carbon($dateTo));
        }
        if (!is_null(\Auth::user()->shop_ids)) {
            $model->whereIn('shop_id', json_decode(\Auth::user()->shop_ids));
        }
        $countOrders = $model->count();
        $countProducts = $this->countProducts($dateFrom, $dateTo);
        if (isset($countProducts[0]) && $countProducts[0]->cc > 0 && $countOrders > 0) {
            return round($countProducts[0]->cc / $countOrders, 2);
        }
        return 0;
    }

    /**
     * @param \Jenssegers\Mongodb\Relations\EmbedsOneOrMany $relation
     * @param string $param
     * @param mixed $value
     * @return Model|null|static
     */
    public function findProductByParam(\Jenssegers\Mongodb\Relations\EmbedsOneOrMany $relation, string $param, $value)
    {
        if ($result = $relation->where($param, '=', $value)->first()) {
            return $result;
        } else {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Not found embed relation " . class_basename($relation));
        }
    }

    /**
     * @param \Jenssegers\Mongodb\Relations\EmbedsOneOrMany $relation
     * @return array|null
     */
    public function GetEmbedProductsById(\Jenssegers\Mongodb\Relations\EmbedsOneOrMany $relation)
    {
        /**
         * @var $relation \Jenssegers\Mongodb\Relations\EmbedsOneOrMany
         */
        if ($result = $relation) {
            return $relation->getEmbedded();
        } else {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Not found embed relation " . class_basename($relation));
        }
    }

    /**
     * @param string $productId
     * @return Collection|null
     */
    public function findByProductId(string $productId)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        return $model->where('products.product_id', $productId)->get();
    }


    /**
     * @param string $sourceId
     * @return Order[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function findByOrderSourceId(string $sourceId)
    {
        /**
         * @var $model Order
         */
        $model = $this->modelClassName;
        $model = $model::query();
        return $model->where('source_id', $sourceId)->get();
    }


    public function createWithCustomer($attributes)
    {
//        $model = $this->modelClassName;
//        $model::create($attributes);
    }

    /**
     * @param $relation
     * @param $value
     * @return mixed
     */
    public function findEmbedByProductId($relation, $value)
    {

        if ($result = $relation->where('_id', '=', $value)->first()) {
            return $result;
        } else {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException("Not found embed relation " . class_basename($relation));
        }
    }

    /**
     * @param array $data
     * @param string $id
     * @return mixed
     * @throws boundException
     */
    public function update(array $data, string $id)
    {
        if (isset($data['payment_status_id'])) {

        } else {
            if ($this->checkStatusBlock($id)) {
                throw new boundException('bound', 'OrderStatusBlocked');
            }
        }
        return parent::update($data, $id);
    }

    /**
     * @param string $id
     * @return bool
     */
    public function checkStatusBlock(string $id)
    {
        /**
         * @var $model \App\Models\PerAccountsModels\MongoModels\Order
         */
        $model = $this->find($id);
        return $model->status_id > self::STATUS_PROGRESS;
    }

}