<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 12/18/17
 * Time: 11:10 AM
 */

namespace App\Repositories\PerAccountsRepositories\Contracts;


use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

interface OrderRepositoryInterface extends RepositoryInterface
{

    const STATUS_NEW       = 1;
    const STATUS_PROGRESS  = 2;
    const STATUS_FORMATION = 3;
    const STATUS_SHIPMENT  = 4;
    const STATUS_SHIPPED   = 5;
    const STATUS_DELIVERED = 6;
    const STATUS_FINISHED  = 7;
    const STATUS_DEFERRED  = 8;
    const STATUS_CANCELED  = 9;
    const STATUS_DELETED   = 10;

//    const DELIVERY_TYPE_TRANSPORT_COMPANY = 1;
//    const DELIVERY_TYPE_COURIER           = 2;
//    const DELIVERY_TYPE_LOCAL_PICKUP      = 3;

    const PAYMENT_TYPE_COD        = 1;
    const PAYMENT_TYPE_PREPAYMENT = 2;

    const PAYMENT_STATUS_NOT_PAID = 1;
    const PAYMENT_STATUS_PAID     = 2;

    const SHIPMENT_TYPE_SINGLE   = 'single';
    const SHIPMENT_TYPE_MULTIPLE = 'multiple';

    /**
     * @return mixed
     */
    public static function getStatuses();

//    public static function getDeliveryTypes();

    /**
     * @return mixed
     */
    public static function getPaymentTypes();

    /**
     * @return mixed
     */
    public static function getPaymentStatuses();

    /**
     * @param Model $order
     * @param int $statusId
     * @return mixed
     */
    public function setStatus(Model $order, int $statusId);

    /**
     * @param string $id
     * @return mixed
     */
    public function findOneWithCustomersAndProducts(string $id);

    /**
     * @param string $shopId
     * @param string $externalId
     * @return mixed
     */
    public function findOneByExternalId(string $shopId, string $externalId);

    /**
     * @param int|null $statusId
     * @return mixed
     */
    public function findAllWithCustomersAndProducts(int $statusId = null);


    /**
     * @param string $customerId
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findByCustomerWithCustomersAndProducts(string $customerId);

    /**
     * @param $num string
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function findAllByNum(string $num);

    /**
     * @param $statusId int
     * @param $dateFrom string
     * @param $dateTo string
     * @param $payed bool
     * @return integer
     */
    public function countByStatus(int $statusId = null, string $dateFrom = null, string $dateTo = null, bool $payed = null);

    /**
     * @param $statusId int
     * @param $dateFrom string
     * @param $dateTo string
     * @param $payed int
     * @return integer
     */
    public function sumByStatus(int $statusId = null, string $dateFrom = null, string $dateTo = null, int $payed = null);

    /**
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @param null|int $payed
     * @return float|int
     */
    public function averageCheck(string $dateFrom = null, string $dateTo = null, int $payed = null);

    /**
     * @param $shopId string
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByShop(string $shopId, string $dateFrom = null, string $dateTo = null);

    /**
     * @param $delivery integer
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByDelivery(int $delivery, string $dateFrom = null, string $dateTo = null);

    /**
     * @param $transporter integer
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByDeliveryTransporter(int $transporter, string $dateFrom = null, string $dateTo = null);

    /**
     * @param $payment integer
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByPayment(int $payment, string $dateFrom = null, string $dateTo = null);

    /**
     * @param $relation
     * @param $value
     * @return mixed
     */
    public function findEmbedByProductId($relation, $value);
    /**
     * @param $status integer
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByPaymentStatus(int $status, string $dateFrom = null, string $dateTo = null);

    /**
     * @param $area string
     * @param $dateFrom string|null
     * @param $dateTo string|null
     * @return integer
     */
    public function countByNPArea(string $area, string $dateFrom = null, string $dateTo = null);

    /**
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return mixed
     */
    public function countProducts(string $dateFrom = null, string $dateTo = null);

    /**
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return mixed
     */
    public function getOrdersByHours(string $dateFrom = null, string $dateTo = null);

    /**
     * @param null|int $status_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return mixed
     */
    public function countOverdueOrdersByUsers(int $status_id = null, string $dateFrom = null, string $dateTo = null);

    /**
     * @param null|int $status_id
     * @param null|int $user_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return int
     */
    public function countGreen(int $status_id = null, int $user_id = null, string $dateFrom = null, string $dateTo = null);

    /**
     * @param null|int $status_id
     * @param null|int $user_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return int
     */
    public function countRed(int $status_id = null, int $user_id = null, string $dateFrom = null, string $dateTo = null);

    /**
     * @param int $status_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return int
     */
    public function countOverdue(int $status_id, string $dateFrom = null, string $dateTo = null);

    /**
     * @param int $status_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return int|mixed
     * @throws \Exception
     */
    public function avgTime(int $status_id, string $dateFrom = null, string $dateTo = null);

    /**
     * @param null|int $status_id
     * @param null|int $user_id
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return int|mixed
     */
    public function avgExecuteTime(int $status_id = null, int $user_id = null, string $dateFrom = null, string $dateTo = null);

    /**
     * @param null|string $dateFrom
     * @param null|string $dateTo
     * @return float|int
     */
    public function avgProducts(string $dateFrom = null, string $dateTo = null);

    /**
     * @param \Jenssegers\Mongodb\Relations\EmbedsOneOrMany $relation
     * @param string $param
     * @param mixed $value
     * @return Model|null|static
     */
    public function findProductByParam(\Jenssegers\Mongodb\Relations\EmbedsOneOrMany $relation, string $param, $value);

    /**
     * @param \Jenssegers\Mongodb\Relations\EmbedsOneOrMany $relation
     * @return array|null
     */
    public function GetEmbedProductsById(\Jenssegers\Mongodb\Relations\EmbedsOneOrMany $relation);

    /**
     * @param string $productId
     * @return \Jenssegers\Mongodb\Collection|null
     */
    public function findByProductId(string $productId);

    /**
     * @param array $data
     * @param string $id
     * @return mixed
     */
    public function update(array $data, string $id);


    /**
     * @param string $sourceId
     * @return mixed
     */
    public function findByOrderSourceId(string $sourceId);

    /**
     * @param string $id
     * @return bool
     */
    public function checkStatusBlock(string $id);
}