<?php
/**
 * Created by PhpStorm.
 * User: nastia
 * Date: 28.02.18
 * Time: 14:25
 */

namespace App\Services\Import\Classes\Product;

use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use App\Exceptions\integrationException;
use App\Exceptions\importException;
class ImportPromua extends BaseImportClass implements ImportImplementationInterface
{
    const PER_PAGE = 10;
    public function prepare($importId)
    {
        parent::prepare($importId);
        $offset = 0;
        while (count($products = $this->getProducts($offset)) > 0) {
            foreach ($products as $product) {
                /**
                 * @var $model \App\Services\Import\Models\ImportProductModel
                 */
                $model = $this->storage->getItemModel();

                $model->id = $product['id'];
                $model->name = $product['name'];
                $model->sku = (!empty($product['sku'])) ? $product['sku'] : (string)$product['id'];
//                var_dump($model->sku);
                $model->status = ($product['status'] == 'on_display') ?
                    ProductRepositoryInterface::STATUS_ACTIVE :
                    ProductRepositoryInterface::STATUS_DISABLED;
                $model->type = ProductRepositoryInterface::TYPE_SINGLE;
                $model->description = $product['description'];
                $model->unit = ProductRepositoryInterface::UNIT_PIECE;

                $price = [
                    [
                        'price_type_id' => $this->import->import_price_id ?? $this->shop->price_type_id,
                        'price' => (float)$product['price'],
                        'currency_id' => $this->shop->currency_id,
                    ]
                ];
                $model->prices = $price;

                $model->stocks = [
                    [
                        'warehouse_id' =>  $this->import->import_warehouse_id,
                        'qty' => ($product['presence'] == 'available') ? 1 : 0,
                    ]
                ];

                if ($this->import->create_categories && isset($product['category']) && is_array($product['category'])) {
                    $model->categories = $this->getCategories($product['category']);
                }
                $model->images = $this->getImages($product['images']);
                $model->shops = $this->getShop($product);
                $this->storage->pushDirty($model);
                $offset = $product['id'];
            }

        }
    }


    /**
     * @param array $images
     * @return array
     */
    public function getImages($images)
    {
        $data = [];
        foreach ($images as $image) {
            $data[] = [
                'path' => null,
                'url' => $image['url']
            ];
        }
        return $data;
    }

    /**
     * @param array $categories
     * @return array
     */
    private function getCategories($categories)
    {
        //TODO for next version
        $category = $this->categoryRepository->findBy('name', 'Import');
        if (!$category) {
            $category = $this->categoryRepository->create(['name' => 'Import']);
        }
        return [$category->_id];
    }

    /**
     * @param array $product
     * @return array
     */
    public function getShop($product)
    {
        return [
            [
                'shop_id' => $this->shop->_id,
                'shop_product_id' => (string)$product['id'],
                'shop_sku' => (!empty($product['sku'])) ? $product['sku'] : (string)$product['id'],
                'price_type_id' => $this->shop->price_type_id,
                'shop_product_url' => $this->shop->settings->shop_url . '/p'. (string)$product['id'] .'-product.html',
                'active' => ($product['status'] == 'on_display') ?
                    ProductRepositoryInterface::STATUS_ACTIVE :
                    ProductRepositoryInterface::STATUS_DISABLED,
            ]
        ];
    }

    /**
     * @param int $offset
     * @return array
     * @throws importException
     */
    private function getProducts($offset)
    {
        try {
            $response = $this->integration->product()->getAll($offset, self::PER_PAGE);
            if (isset($response['products'])) {
                return $response['products'];
            }
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }

    }
}