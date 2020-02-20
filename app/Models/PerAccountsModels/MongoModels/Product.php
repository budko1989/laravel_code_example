<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/20/17
 * Time: 12:46 PM
 */

namespace App\Models\PerAccountsModels\MongoModels;

use App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductAttribute;
use App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductImage;
use App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductPrice;
use App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductShop;
use App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductStock;
use App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductSupplierInfo;
use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use Jenssegers\Mongodb\Eloquent\SoftDeletes;

/**
 * @property integer $_id
 * @property string $name
 * @property int $status
 * @property int $type
 * @property string $sku
 * @property string $barcode
 * @property int $qty
 * @property int $author_id
 * @property int $parent_id
 * @property string $description
 * @property string $volume
 * @property string $weight
 * @property int $unit
 * @property string $created_at
 * @property string $updated_at
 * @property array $flattened
 * @property string $category
 * @property string $clean_weight
 * @property string $image
 * @property int $sales_complete
 * @property int $sales
 *
 * @property array $labels
 * @property Category[] $categories
 * @property ProductPrice[] $prices
 * @property ProductImage[] $images
 * @property Product[] $children
 * @property Product $parent
 * @property ProductSupplierInfo[] $suppliers
 * @property ProductStock[] $stocks
 * @property ProductShop[] $shops
 * @property ProductAttribute[] $attributes
 */
class Product extends BasePerAccountModel
{
    use SoftDeletes;

    /**
     * @var bool
     */
    public $needSync = true;

    /**
     * Атрибуты, которые должны быть преобразованы в даты.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'status',
        'type',
        'sku',
        'barcode',
        'parent_id',
        'description',
        'volume',
        'weight',
        'unit',
        'sales_complete',
        'sales'
    ];

    protected $attributes = [
        'status' => ProductRepositoryInterface::STATUS_ACTIVE,
        'type' => ProductRepositoryInterface::TYPE_SINGLE,
        'volume' => '',
        'weight' => '',
        'unit' => ProductRepositoryInterface::UNIT_PIECE,
    ];

    public $flattenedRelations = [
        'suppliers' => [
            'key' => 'supplier_id',
            'fields' => [
                'sku',
                'sale_price',
                'stock'
            ],
            'class' => Supplier::class
        ],
        'prices' => [
            'key' => 'price_type_id',
            'fields' => ['price'],
            'class' => PriceType::class
        ],
        'stocks' => [
            'key' => 'warehouse_id',
            'fields' => ['qty'],
            'class' => Warehouse::class
        ],
        'shops' => [
            'key' => 'shop_id',
            'fields' => [
                'shop_product_id',
                'shop_sku',
                'active',
                'price_type_id',
                'shop_product_url'
            ],
            'class' => Shop::class
        ],
        'attributes' => [
            'key' => 'attribute_id',
            'fields' => ['value'],
            'class' => CustomAttribute::class
        ]
    ];

    public function getLabels() {

        return [
            'name' => ['label' => trans('validation.attributes.name'), 'type' => 'string', 'col_renderer' => 'name'],
            'status' => ['label' => trans('validation.attributes.status'), 'type' => 'select', 'col_renderer' => 'status'],
            'type' => ['label' => trans('validation.attributes.type'), 'type' => 'select', 'col_renderer' => 'type'],
            'sku' => ['label' => trans('validation.attributes.sku'), 'type' => 'string', 'col_renderer' => null],
            'qty' => ['label' => trans('validation.attributes.qty'), 'type' => 'int', 'col_renderer' => null],
//            'description' => ['label' => trans('validation.attributes.description'), 'type' => 'text'],
            'volume' => ['label' => trans('validation.attributes.volume'), 'type' => 'string', 'col_renderer' => 'volume'],
            'weight' => ['label' => trans('validation.attributes.weight'), 'type' => 'string', 'col_renderer' => 'weight'],
            'unit' => ['label' => trans('validation.attributes.unit'), 'type' => 'select', 'col_renderer' => 'Unit'],
            'category' => ['label' => trans('validation.attributes.category'), 'type' => 'string', 'col_renderer' => null],
            'image' => ['label' => trans('validation.attributes.image'), 'type' => 'string', 'col_renderer' => 'image'],
            'sales_complete' => ['label' => trans('validation.attributes.sales_complete'), 'type' => 'string', 'col_renderer' => null],
            'sales' => ['label' => trans('validation.attributes.sales'), 'type' => 'string', 'col_renderer' => null],
        ];
    }

    public function getCategoryAttribute()
    {
        if ($this->category_ids && !empty($this->category_ids)){
            $names = [];
            foreach ($this->categories as $category) {
                $names[] = $category->name;
            }
            return ($names) ? implode(', ', $names) : '';
        }
        return '';
    }

    public function getCleanWeightAttribute()
    {
        if ($this->weight) {
            return (int)preg_replace('/[^\d]/', '',$this->weight);
        }
        return 0;
//        return explode(':', $this->weight)[0];
    }

    public function getImageAttribute()
    {
        foreach ($this->images as $image) {
            return $image->getUrl();
        }
        return null;
    }

//    public function categories()
//    {
//        return $this->belongsToMany('App\Models\PerAccountsModels\MysqlModels\Category', null, 'product_ids', 'category_ids');
//    }

    public function children()
    {
        return $this->hasMany('App\Models\PerAccountsModels\MongoModels\Product', 'parent_id', '_id');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\PerAccountsModels\MongoModels\Product', 'parent_id', '_id');
    }

    public function categories()
    {
        return $this->belongsToMany('App\Models\PerAccountsModels\MongoModels\Category', null, 'product_ids', 'category_ids');
    }

    public function images()
    {
        return $this->embedsMany('App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductImage');
    }

    public function suppliers()
    {
        return $this->embedsMany('App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductSupplierInfo')->with(['supplier', 'currency']);
    }

    public function supplier()
    {
        return $this->hasOne('App\Models\PerAccountsModels\MongoModels\Supplier', '_id', 'supplier_id');
    }

    public function shops()
    {
        return $this->embedsMany('App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductShop')->with(['shop']);
    }

    public function shop()
    {
        return $this->hasOne('App\Models\PerAccountsModels\MongoModels\Shop', '_id', 'shop_id')->with(['currency']);
    }

    public function prices()
    {
        return $this->embedsMany('App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductPrice')->with(['currency']);
    }

    public function currency()
    {
        return $this->hasOne('App\Models\PerAccountsModels\MongoModels\Currency', '_id', 'currency_id');
    }

    public function stocks()
    {
        return $this->embedsMany('App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductStock');
    }

//    public function customAttributes()
//    {
//        return $this->embedsMany('App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductAttribute');
//    }

    public function attributes()
    {
        return $this->embedsMany('App\Models\PerAccountsModels\MongoModels\ProductEmbedModels\ProductAttribute', 'attributes')->with(['attributeValues']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributeValues()
    {
        return $this->hasMany('App\Models\PerAccountsModels\MongoModels\CustomAttributeValue', 'attribute_id', 'attribute_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function attributeValue()
    {
        return $this->hasOne('App\Models\PerAccountsModels\MongoModels\CustomAttributeValue', '_id', 'attribute_value_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function attribute()
    {
        return $this->hasOne('App\Models\PerAccountsModels\MongoModels\CustomAttribute', '_id', 'attribute_id');
    }

}