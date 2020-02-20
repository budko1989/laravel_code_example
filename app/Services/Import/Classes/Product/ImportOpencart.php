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
use App\Repositories\PerAccountsRepositories\Contracts\ProductAttributeRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Models\ImportProductModel;

class ImportOpencart extends BaseImportClass implements ImportImplementationInterface
{
    const PER_PAGE = 10;

    public function prepare($importId)
    {
        parent::prepare($importId);

        $offset = 0;
        while (count($products = $this->getProducts($offset)) > 0) {
            foreach ($products as $product) {
                /**
                 * @var $model ImportProductModel
                 */
                $model = $this->storage->getItemModel();

                $model->id = $product['product_id'];
                $model->name = $product['name'];
                $model->sku = $product['model'];
                $model->status = ($product['status'] == 1) ? ProductRepositoryInterface::STATUS_ACTIVE : ProductRepositoryInterface::STATUS_DISABLED;
                $model->type = (isset($product['options']) && is_array($product['options']) && count($product['options']) > 0) ? ProductRepositoryInterface::TYPE_CONFIGURABLE : ProductRepositoryInterface::TYPE_SINGLE;
                $model->description = $product['description'];
                $model->volume = $this->getVolume($product);
                $model->weight = $this->getWeight($product);
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
                        'qty' => $product['quantity'],
                    ]
                ];

                if ($this->import->create_categories && isset($product['categories']) && is_array($product['categories'])) {
                    $model->categories = $this->getCategories($product['categories']);
                }
                $model->images = $this->getImages($product['images']);
                $model->custom_attributes = $this->getAttributes($product['attributes']);
                $model->shops = $this->getShop($product);

                $this->storage->pushDirty($model);
                if (isset($product['options']) && is_array($product['options']) && count($product['options']) > 0) {
                    $this->pushEmbedProducts($model, $product['options']);
                }
            }
            $offset += self::PER_PAGE;
        }
    }

    private function pushEmbedProducts(ImportProductModel $model, array $options)
    {
        foreach ($options as $option) {
            foreach ($option['product_option_value'] as $item) {
                $newModel = clone $model;
                $newModel->name = $model->name.' '.$option['name'].': '.$item['name'];
                $newModel->sku = $model->sku.' '.$option['name'].': '.$item['name'];
                $newModel->type = ProductRepositoryInterface::TYPE_EMBED;
                $newModel->parent_id = $model->id;
                $newModel->images = $this->getImages([['image' => $item['image']]]);
                $newModel->shops = $this->getShopForEmbed($model, $option, $item);

                $this->storage->pushDirty($newModel);
            }
        }
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getAttributes($attributes)
    {
        $data = [];
        foreach ($attributes as $attributeGroupe) {
            foreach ($attributeGroupe['attribute'] as $attribute) {
                if ($attribute['text']) {
                    $data[] = [
                        'name' => $attribute['name'],
                        'type' => ProductAttributeRepositoryInterface::TYPE_SELECT,
                        'value' => $attribute['text']
                    ];
                }
            }
        }
        return $data;
    }

    /**
     * @param array $images
     * @return array
     */
    public function getImages($images)
    {
        $data = [];
        foreach ($images as $image) {
            if (isset($image['image']) && !empty($image['image'])) {
                $data[] = [
                    'path' => null,
                    'url' => $this->shop->settings->url.'/image/'.$image['image']
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
                'shop_product_id' => $product['product_id'],
                'shop_product_url' => $this->shop->settings->url .'/index.php?route=product/product&product_id=' . $product['product_id'],
                'shop_sku' => $product['model'],
                'price_type_id' => $this->shop->price_type_id,
                'import_id' => $this->importId,
                'active' => ($product['status']),
            ]
        ];
    }

    public function getShopForEmbed($product, $option, $item)
    {

        $shopProductId[] = [
            'productId' =>  $product->id,
            'options' => $this->prepareProductOptionData($option, $item)
        ];

        return [
            [
                'shop_id' => $this->shop->_id,
                'shop_product_id' => isset($shopProductId) ? json_encode($shopProductId) : $product['product_id'],
                'shop_product_url' => $this->shop->settings->url .'/index.php?route=product/product&product_id=' . $product['product_id'],
                'shop_sku' => $product->model,
                'price_type_id' => $this->shop->price_type_id,
                'import_id' => $this->importId,
                'active' => ($product->status),
            ]
        ];
    }

    public function prepareProductOptionData($option, $item)
    {

                $data[] = [
                    'option_id' => $option['option_id'],
                    'option_value_id' => $item['option_value_id']
                ];

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
     * @return null|string
     */
    private function getVolume($product)
    {
        if (!isset($product['length'])) return null;
        if (!isset($product['width'])) return null;
        if (!isset($product['height'])) return null;
        return (float)$product['length'].'x'.(float)$product['width'].'x'.(float)$product['height'].':'.$this->getVolumeUnit($product['length_class_id']);
    }

    /**
     * @param array $product
     * @return null|string
     */
    private function getWeight($product)
    {
        if (!isset($product['weight'])) return null;
        return (float)$product['weight'].':'.$this->getWeightUnit($product['weight_class_id']);
    }

    /**
     * @param string $value
     * @return string
     */
    private function getVolumeUnit($value)
    {
        switch ($value) {
            case '1':
                return ProductRepositoryInterface::VOLUME_UNIT_SM;

            case '2':
                return ProductRepositoryInterface::VOLUME_UNIT_MM;

            default:
                return '';
        }
    }

    /**
     * @param string $value
     * @return string
     */
    private function getWeightUnit($value)
    {
        switch ($value) {
            case '1':
                return ProductRepositoryInterface::WEIGHT_UNIT_KG;

            case '2':
                return ProductRepositoryInterface::WEIGHT_UNIT_G;

            default:
                return '';
        }
    }

    /**
     * @param int $offset
     * @return array
     * @throws importException
     */
    private function getProducts($offset) {
        try {
            $response = $this->integration->product()->getAll($offset);
            if (isset($response['products'])) {
                return $response['products'];
            }
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }

    }
}