<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 1/15/18
 * Time: 12:56 PM
 */

namespace App\Services\Import\Classes\Product;

use App\Exceptions\importException;
use App\Exceptions\integrationException;
use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Models\ImportProductModel;

class ImportM2m extends BaseImportClass implements ImportImplementationInterface
{

    public function prepare($importId)
    {
        parent::prepare($importId);

        $page = 1;
        $data = $this->getProducts($page);
        while ($data['lastPage'] >= $page) {
            $data = $this->getProducts($page);
            foreach ($data['products'] as $product) {
                /**
                 * @var $model ImportProductModel
                 */
                $model = new ImportProductModel();
//                $model = $this->storage->getItemModel();
                $model->id = $product['id'];
                $model->name = $product['title'];
                $model->sku = (string)$product['id'];
                $model->status = ($product['active']) ? ProductRepositoryInterface::STATUS_ACTIVE : ProductRepositoryInterface::STATUS_DISABLED;
                $model->type = ProductRepositoryInterface::TYPE_CONFIGURABLE;
                $model->description = $product['description'];
                $model->volume = '0x0x0:';
                $model->weight = '0:1';
                $model->unit = ProductRepositoryInterface::UNIT_PIECE;

                if ($this->import->create_categories && isset($product['categories']) && is_array($product['categories'])) {
                    $model->categories = $this->getCategories($product['categories']);
                }
                $model->images = $this->getImages($product['images']);
                $model->shops = $this->getShop($product);

                $model->parent_id = null;
                $model->stocks = [];
                $model->prices = [];

                $this->storage->pushDirty($model);
                if (isset($product['sizes']) && is_array($product['sizes']) && count($product['sizes']) > 0) {
                    $this->pushEmbedProducts($model, $product);
                }
            }
            $page += 1;
            // if ($page == 2) return;
        }
    }

    private function pushEmbedProducts(ImportProductModel $model, $product)
    {
        foreach ($product['sizes'] as $size) {
            /**
             * @var $newModel ImportProductModel
             */
            $newModel = new ImportProductModel();
//            $newModel = $this->storage->getItemModel();
            $newModel->id = $size['pivot']['id'];
            $newModel->name = $model->name.', Размер: '.$size['length'];
            $newModel->sku = $product['id'].'/'.$size['pivot']['id'];
            $newModel->type = ProductRepositoryInterface::TYPE_EMBED;
            $newModel->status = ($product['active']) ? ProductRepositoryInterface::STATUS_ACTIVE : ProductRepositoryInterface::STATUS_DISABLED;
            $newModel->description = $product['description'];
            $newModel->volume = '0x0x0:';
            $newModel->weight = '0:1';
            $newModel->unit = ProductRepositoryInterface::UNIT_PIECE;
            $newModel->parent_id = (string)$model->id;
            $newModel->shops = $this->getShopForEmbed($model, $size);

            $newModel->prices = [
                [
                    'price_type_id' => $this->shop->price_type_id,
                    'price' => (float)$product['price'],
                    'currency_id' => $this->shop->currency_id,
                ]
            ];

            $newModel->stocks = [
                [
                    'warehouse_id' =>  $this->import->import_warehouse_id,
                    'qty' => $size['pivot']['quantity'],
                ]
            ];
            $this->storage->pushDirty($newModel);
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
            if (isset($image['imageUrl']) && !empty($image['imageUrl'])) {
                $data[] = [
                    'path' => null,
                    'url' => $image['imageUrl']
                ];
            }
        }
        return $data;
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
                'shop_product_url' => 'https://m2mtop.com/products/' . $product['id'],
                'shop_sku' => (string)$product['id'],
                'price_type_id' => $this->shop->price_type_id,
                'import_id' => $this->importId,
                'active' => $product['active'],
            ]
        ];
    }

    public function getShopForEmbed($product, $size)
    {
        return [
            [
                'shop_id' => $this->shop->_id,
                'shop_product_id' => $product['id'].'/'.$size['pivot']['id'],
                'shop_product_url' => 'https://m2mtop.com/products/' . $product['id'],
                'shop_sku' => $product['id'].'/'.$size['pivot']['id'],
                'price_type_id' => $this->shop->price_type_id,
                'import_id' => $this->importId,
                'active' => $product['active'],
            ]
        ];
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
     * @param int $offset
     * @return array
     * @throws importException
     */
    private function getProducts($offset) {
        try {
            $response = $this->integration->product()->getAll($offset);
            return $response['data'];
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }

    }
}