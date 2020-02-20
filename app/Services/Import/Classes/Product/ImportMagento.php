<?php
/**
 * Created by PhpStorm.
 * User: nastia
 * Date: 22.01.18
 * Time: 15:20
 */

namespace App\Services\Import\Classes\Product;


use App\Repositories\PerAccountsRepositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ImportRepositoryInterface;
use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Contracts\StorageRepositoryInterface;
use App\Services\Import\Models\ImportAttributeModel;
use App\Services\Integration\Contracts\IntegrationServiceInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ProductAttributeRepositoryInterface;
use App\Exceptions\integrationException;
use App\Exceptions\importException;
use Illuminate\Support\Collection;

class ImportMagento extends BaseImportClass implements ImportImplementationInterface
{
    const PER_PAGE = 100;

    /**
     * @var mixed|App\Services\Import\StorageAttributeRepository
     */
    protected $attributeStorage;

    /**
     * @var array
     */
    protected $unwantedAttributes = [
        "small_image",
        "thumbnail",
        "options_container",
        "required_options",
        "has_options",
        "url_key",
        "msrp_display_actual_price_type"
    ];

    /**
     * @var Collection
     */
    private $attributesCollection;

    /**
     * ImportMagento constructor.
     * @param StorageRepositoryInterface $storage
     * @param ImportRepositoryInterface $importRepository
     * @param IntegrationServiceInterface $integrationService
     * @param CategoryRepositoryInterface $categoryRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        StorageRepositoryInterface $storage,
        ImportRepositoryInterface $importRepository,
        IntegrationServiceInterface $integrationService,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository
    )
    {
        parent::__construct($storage, $importRepository, $integrationService, $categoryRepository, $productRepository, $customerRepository);
        $this->attributeStorage = resolve('App\Services\Import\StorageAttributeRepository');
    }

    /**
     * @param $importId
     * @return mixed|void
     * @throws importException
     */
    public function prepare($importId)
    {
        parent::prepare($importId);
        $this->attributeStorage->setImportId($importId);
        $this->attributeStorage->setImportItemModel(new ImportAttributeModel());

        if(!$this->checkAttributes()) {
            throw new importException('has no attributes');
        }

        $this->getAllData();
        $this->attributeStorage->deleteData();
    }


    /**
     * @return bool
     */
    private function checkAttributes()
    {
        $this->attributesCollection = $this->attributeStorage->popDirty();
        if ($this->attributesCollection->isEmpty()) {
            return false;
        }
        return true;
    }

    /**
     * @throws importException
     */
    public function getAllData()
    {
        $offset = 1;
        $counter = 0;
        do {
            $products = $this->getProducts($offset);
            $counter += count($products['items']);
            if (!isset($total_count)) {
                $total_count = $products['total_count'];
            }
            foreach ($products['items'] as $product) {
                /**
                 * @var $model \App\Services\Import\Models\ImportProductModel
                 */
                $model = $this->storage->getItemModel();
                $model->id = $product['id'];
                $model->name = $product['name'];
                $model->sku = $product['sku'];
                $model->status = ($product['status'] == 1) ?
                    ProductRepositoryInterface::STATUS_ACTIVE :
                    ProductRepositoryInterface::STATUS_DISABLED;
                $model->type = ProductRepositoryInterface::TYPE_SINGLE;
                $attributes = $this->getAttributes($product);
                $model->description = $attributes['description'] ?? '';
                unset($attributes['description']);
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
                        'qty' => isset($product['stockItem']) ?
                            $product['stockItem']['qty'] :
                            $product['status']
                    ]
                ];

                if ($this->import->create_categories) {
                    $model->categories = $this->getCategories([]);
                }
                $model->images = isset($attributes['image']) ?
                    $this->getImages([$attributes['image']]) :
                    [];
                unset($attributes['image']);
                $model->custom_attributes = $attributes;
                $model->shops = $this->getShop($product);
                $this->storage->pushDirty($model);
            }
            $offset++;
        }
        while($counter < $total_count);
    }

    /**
     * @param array $product
     * @return array
     */
    private function getAttributes(array $product)
    {
        $customAttributes = [];
        foreach ($product['custom_attributes'] as $key => $redisAttribute) {
            if($redisAttribute['attribute_code'] == "description") {
                $customAttributes['description'] = $redisAttribute['value'];
                continue;
            }
            if($redisAttribute['attribute_code'] == "image") {
                $customAttributes['image'] = $redisAttribute['value'];
                continue;
            }

            /**
             * Clear attributes which not needs
             */
            if(in_array($redisAttribute['attribute_code'], $this->unwantedAttributes)) {
                continue;
            }


            $attributes = $this->getAttributesArray($redisAttribute['attribute_code']);
            if (preg_match('/(\d+\,)+/', $redisAttribute['value'])) {
                $values = explode(',', $redisAttribute['value']);
                foreach ($values as $dirtyValue) {
                    $value = $this->getAttributeLabel(
                        isset($attributes['options']) ? $attributes['options'] : null,
                        $dirtyValue);
                    $customAttributes[] = [
                        'name' => $attributes['label'] ?? $redisAttribute['attribute_code'],
                        'type' => ProductAttributeRepositoryInterface::TYPE_SELECT,
                        'value' => $value ?? $redisAttribute['value'],
                    ];
                }
            }
            else {
                $value = $this->getAttributeLabel(
                    isset($attributes['options']) ? $attributes['options'] : null,
                    $redisAttribute['value']);
                $customAttributes[] = [
                    'name' => $attributes['label'] ?? $redisAttribute['attribute_code'],
                    'type' => ProductAttributeRepositoryInterface::TYPE_SELECT,
                    'value' => $value ?? $redisAttribute['value'],
                ];
            }
        }
        return $customAttributes;
    }

    /**
     * @param $code
     * @return mixed
     */
    private function getAttributesArray($code)
    {
        $attributeDirtyModel = $this->attributesCollection->where('attribute_code', $code);
        return $attributeDirtyModel->flatten()->toArray()[0];
    }

    /**
     * @param $options
     * @param $productValue
     * @return null
     */
    private function getAttributeLabel($options, $productValue)
    {
        $value = null;
        if (!empty($options)) {
            foreach ($options as $option) {
                if ($option['value'] == $productValue) {
                    $value = $option['label'];
                }
            }
        }
        return $value;
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
                'url' => $this->shop->settings->url.'/pub/media/catalog/product'.$image
            ];
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
                'shop_sku' => $product['sku'],
                'shop_product_url' => $this->shop->settings->url . '/catalog/product/view/id/'. (string)$product['id'],
                'price_type_id' => $this->shop->price_type_id,
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
    private function getWeight($product)
    {

        if (!isset($product['weight'])) return ' ';
        return (float)$product['weight'].':'.$this->shop->settings->weight_unit ?? '';
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
            if (isset($response['items'])) {
                return $response;
            }
        } catch (integrationException $exception) {
            throw new importException($exception->getMessage());
        }

    }
}