<?php

namespace App\Services\Import\Classes\Product;


use App\Exceptions\importException;
use App\Exceptions\integrationException;
use App\Repositories\PerAccountsRepositories\Contracts\ProductAttributeRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Models\ImportProductModel;

class ImportWordpress extends BaseImportClass implements ImportImplementationInterface
{
    const PER_PAGE = 10;

    public function prepare($importId)
    {
        parent::prepare($importId);

        $offset = 0;
        while (count($products = $this->getProducts($offset)) > 0) {
            foreach ($products as $product) {
                $product = (array)$product;
                /**
                 * @var $model ImportProductModel
                 */
                $model = $this->storage->getItemModel();

                $model->id = $product['id'];
                $model->name = $product['name'];
                $model->sku = $product['sku'];
                $model->status = $this->getStatus ($product['status']);
                $model->type = $this->getType($product['type']);
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
                        'warehouse_id' => $this->import->import_warehouse_id,
                        'qty' => $product['stock_quantity'],
                    ]
                ];

                if ($this->import->create_categories && isset($product['categories']) && is_array($product['categories'])) {
                    $model->categories = $this->getCategories($product['categories']);
                }
                $model->images = $this->getImages((array)$product['images']);
                $model->custom_attributes = $this->getAttributes((array)$product['attributes']);
                $model->shops = $this->getShop($product);

                $this->storage->pushDirty($model);
            }
            $offset += self::PER_PAGE;
        }
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getAttributes($attributes)
    {
        $data = [];
        foreach ($attributes as $attribute) {
            $attribute = (array)$attribute;
            $options = (array)$attribute['options'];
//            foreach ($attribute['options'] as $option) {
                $data[] = [
                    'name' => $attribute['name'],
                    'type' => ProductAttributeRepositoryInterface::TYPE_SELECT,
                    'value' => implode('|',$options)
                ];
//            }
        }
        return $data;
    }

    public function getType($type)
    {
        switch ($type) {
            case 'simple':
                return ProductRepositoryInterface::TYPE_SINGLE;
            case 'grouped':
                return ProductRepositoryInterface::TYPE_CONFIGURABLE;
            case 'external':
                return ProductRepositoryInterface::TYPE_EMBED;
            default:
                return ProductRepositoryInterface::TYPE_SINGLE;
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
            if (isset($image['src']) && !empty($image['src'])) {
                $data[] = [
                    'path' => null,
                    'url' => $image['src']
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
                'shop_product_url' => $product['permalink'],
                'shop_sku' => $product['sku'],
                'price_type_id' => $this->shop->price_type_id,
                'import_id' => $this->importId,
                'active' => ($product['status']),
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
     * @param array $product
     * @return null|string
     */
    private function getVolume($product)
    {
        $product['dimensions'] = (array)$product['dimensions'];
        if (!isset($product['dimensions']['length'])) return null;
        if (!isset($product['dimensions']['width'])) return null;
        if (!isset($product['dimensions']['height'])) return null;
        return (float)$product['dimensions']['length'] . 'x' . (float)$product['dimensions']['width'] . 'x' . (float)$product['dimensions']['height'] . ':' . $this->getVolumeUnit();
    }

    /**
     * @param array $product
     * @return null|string
     */
    private function getWeight($product)
    {
        if (!isset($product['weight'])) return null;
        return (float)$product['weight'] . ':' . $this->getWeightUnit();
    }

    /**
     * @param string $value
     * @return string
     */
    private function getVolumeUnit()
    {

        return ProductRepositoryInterface::VOLUME_UNIT_SM;

    }

    /**
     * @param string $value
     * @return string
     */
    private function getWeightUnit()
    {

        return ProductRepositoryInterface::WEIGHT_UNIT_KG;

    }

    /**
     * @param int $offset
     * @return array
     * @throws importException
     */
    private function getProducts($offset)
    {
        try {
            $response = $this->integration->product()->getAll($offset);
            if (isset($response)) {
                return $response;
            }
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }

    }

    private function getStatus($status)
    {
        if($status == 'publish') {
            return ProductRepositoryInterface::STATUS_ACTIVE;
        } else {
            return ProductRepositoryInterface::STATUS_DISABLED;
        }
    }
}