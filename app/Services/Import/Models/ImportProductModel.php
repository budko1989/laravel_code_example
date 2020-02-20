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
 * @property string $name
 * @property int $status
 * @property int $type
 * @property string $sku
 * @property string $description
 * @property string $volume
 * @property string $weight
 * @property int $unit
 * @property array $categories
 * @property array $images
 * @property array $prices
 * @property array $custom_attributes
 * @property array $shops
 * @property array $stocks
 * @property string $parent_id
 *
 */
class ImportProductModel extends ImportItemModel
{

    protected $fillable = [
        'id',
        'name',
        'status',
        'type',
        'sku',
        'description',
        'volume',
        'weight',
        'unit',
        'categories',
        'images',
        'prices',
        'custom_attributes',
        'stocks',
        'shops',
        'parent_id'
    ];

}