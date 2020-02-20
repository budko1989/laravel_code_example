<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/27/17
 * Time: 12:14 PM
 */

namespace App\Services\Import\Classes\Product;


use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Classes\BaseImportClass;

class ImportForsage extends BaseImportClass implements ImportImplementationInterface
{

    /**
     * @var array
     */
    public $prices = [];

    public function prepare($importId)
    {
        parent::prepare($importId);
        $this->preparePrices();
//        $this->prepareShortData();
        $this->prepareBaseData();

    }

    public function preparePrices()
    {
        if (isset($this->shop->prices)) {
            foreach ($this->shop->prices as $item) {
                foreach ($item as $key => $value) {
                    $this->prices[$key] = $value;
                }
            }
        }
    }

    public function prepareShortData()
    {
        $products = \DB::connection('forsage')->table('products')->orderBy('id')->offset(173)->limit(10)->get();
        if ($products) {
            foreach ($products as $product) {
                $this->sendProduct($product);

            }
        }
    }

    public function prepareBaseData()
    {
        \DB::connection('forsage')->table('products')->orderBy('id')->chunk(10, function ($products) {
            foreach ($products as $product) {
                $this->sendProduct($product);
            }
        });
    }

    public function sendProduct($product)
    {
        $row = [
            'name' => $product->name,
            'status' => ProductRepositoryInterface::STATUS_ACTIVE,
            'type' => ProductRepositoryInterface::TYPE_SINGLE,
            'sku' => $product->vcode,
            'qty' => $product->quantity,
//                'description' => '',
//                'volume' => '0x0x0:m',
//                'weight' => '10:kg',
            'unit' => ProductRepositoryInterface::UNIT_PIECE,
        ];
//                if ($product->category_id && isset($this->categories[$product->category_id])) {
//                    $row['categories'][] = $this->categories[$product->category_id];
//                }
        foreach ($this->getImages($product->image) as $image) {
            $row['images'][] = $image;
        }
        foreach ($this->getPrices($product->id) as $price) {
            $row['prices'][] = $price;
        }
        foreach ($this->getAttributes($product->id) as $attribute) {
            $row['custom_attributes'][] = $attribute;
        }
        $row['shops'][] = $this->getShop($product);
        $row['stocks'][] = $this->getStock($product);
        $this->storage->pushDirty($row);
    }

    public function getShop($product)
    {
        return [
            "shop_id" => $this->shop->_id,
            "shop_product_id" => $product->id,
            "shop_sku" => $product->vcode,
            "price_type_id" => $this->shop->price_type_id,
//            "promotion_price" => 4234,
            "active" => true,
        ];
    }

    public function getStock($product)
    {
        return [
            "warehouse_id" =>  $this->shop->warehouse_id,
            "qty" => $product->quantity,
        ];
    }

    public function getImages($imageName)
    {
        $data = [];
        if ($imageName) {
            $data[] = [
                'path' => null,
                'url' => 'https://forsage-studio.com/storage/images/products/thumbnails/'.$imageName.'_thumb.jpg'
            ];
//          $row['images'][] = ['path' => $this->productService->downloadImageFromUrl('https://forsage-studio.com/storage/images/products/thumbnails/'.$product->image.'_thumb.jpg')];
        }
        return $data;
    }

    public function getPrices($productId)
    {
        $prices = [];
        $data = \DB::connection('forsage')
            ->table('products_characteristics')
            ->where('product_id', $productId)
            ->whereIn('characteristic_id', [24, 25])
            ->get();

        if ($data) {
            foreach ($data as $price) {
                $prices[] = [
                    'price_type_id' => $this->prices[$price->characteristic_id],
                    'price' => ($price->value) ? $price->value : 0
                ];
            }
        }
        return $prices;
    }

    public function getStocks($productId)
    {
        $prices = [];
        $data = \DB::connection('forsage')
            ->table('products_characteristics')
            ->where('product_id', $productId)
            ->whereIn('characteristic_id', [24, 25])
            ->get();

        if ($data) {
            foreach ($data as $price) {
                $prices[] = [
                    'price_type_id' => $this->prices[$price->characteristic_id],
                    'price' => ($price->value) ? $price->value : 0
                ];
            }
        }
        return $prices;
    }

    public function getAttributes($productId)
    {
        $attributes = [];
        $data = \DB::connection('forsage')
            ->table('products_characteristics')
            ->select(['characteristics.name', 'characteristic_types.name as type_name', 'products_characteristics.value'])
            ->leftJoin('characteristics', 'products_characteristics.characteristic_id', '=', 'characteristics.id')
            ->leftJoin('characteristic_types', 'characteristics.type_id', '=', 'characteristic_types.id')
            ->groupBy('products_characteristics.id')
            ->where('products_characteristics.product_id', $productId)
            ->where('products_characteristics.value', '<>', 'null')
            ->get();

        if ($data) {
            foreach ($data as $attribute) {
                $attributes[] = [
                    'name' => $attribute->name,
                    'type' => 'string',
//                    'type' => $attribute->type_name,
                    'value' => $attribute->value
                ];
            }
        }
        return $attributes;
    }


}