<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 1/12/18
 * Time: 2:21 PM
 */

namespace App\Services\Import\Models;

/**
 * @property string $id
 * @property string $order_date
 * @property string $shop_id
 * @property string $customer_id
 * @property string $payment_info
 * @property string $delivery_info
 * @property string $comment
 * @property string $delivery_type
 * @property string $payment_type
 * @property array $products
 * @property string $delivery_service
 * @property integer $delivery_cost
 * @property array $fields
 *
 */
class ImportOrderModel extends ImportItemModel
{

    protected $fillable = [
        'id',
        'order_date',
        'shop_id',
        'customer_id',
        'payment_info',
        'delivery_info',
        'comment',
        'products',
        'delivery_type',
        'delivery_service',
        'payment_type',
        'fields',
        'delivery_cost'
    ];

}