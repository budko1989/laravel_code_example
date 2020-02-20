<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 1/12/18
 * Time: 1:06 PM
 */

namespace App\Services\Import;


use App\Exceptions\importException;
use App\Exceptions\productException;
use App\Http\Validators\ProductValidator;
use App\Repositories\PerAccountsRepositories\Contracts\ImportErrorRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ImportItemRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ImportRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use App\Services\Import\Contracts\ImportProductServiceInterface;
use App\Services\Import\Contracts\StorageRepositoryInterface;
use App\Services\Import\Models\ImportItemModel;
use App\Services\Import\Models\ImportProductModel;
use App\Services\Product\Contracts\ProductCreateServiceInterface;
use App\Services\Product\Contracts\ProductShopServiceInterface;
use Illuminate\Validation\ValidationException;
use App\Repositories\PerAccountsRepositories\Contracts\ShopRepositoryInterface;

class ImportProductService extends BaseImportService implements ImportProductServiceInterface
{

    /**
     * @var \App\Services\Product\ProductCreateService
     */
    private $productService;

    /**
     * @var \App\Services\Product\ProductShopService
     */
    private $productShopService;

    /**
     * @var \App\Repositories\PerAccountsRepositories\ProductRepository
     */
    private $productRepository;

    public function __construct(
        ImportRepositoryInterface $importRepository,
        ImportErrorRepositoryInterface $importErrorRepository,
        ImportItemRepositoryInterface $importItemRepository,
        StorageRepositoryInterface $storageRepository,
        ProductCreateServiceInterface $productService,
        ProductShopServiceInterface $productShopService,
        ProductRepositoryInterface $productRepository,
        ShopRepositoryInterface $shopRepository
    )
    {
        parent::__construct($importRepository, $importErrorRepository, $importItemRepository, $storageRepository, $shopRepository);
        $this->productService = $productService;
        $this->productShopService = $productShopService;
        $this->productRepository = $productRepository;
    }

    /**
     * Validate item or throw ValidationException
     * @param ImportItemModel $item
     * @throws ValidationException
     * @return void
     */
    protected function validateItem(ImportItemModel $item)
    {
        $product = $this->productRepository->findOneBySkuWithRelations((string)$item->sku);
        if ($product) {
            ProductValidator::validateProduct($item->toArray());
        }
    }

    /**
     * Create or update item or throw importException
     * @param ImportItemModel $item
     * @throws importException
     * @return void
     */
    protected function importItem(ImportItemModel $item)
    {
        /**
         * @var $item ImportProductModel
         */
        try {
            $product = $this->productRepository->findOneBySkuWithRelations((string)$item->sku);
            if ($product) {
                $this->productShopService->addShop($product->_id, [
                    'shop_id' => $item->shops[0]['shop_id'],
                    'shop_sku' => null,
                    'import_id' => $item->shops[0]['import_id'],
                    'shop_product_url' => $item->shops[0]['shop_product_url']
                ], false);
            } else {
                $this->productService->createProduct($item->toArray())->save(false);
            }
        } catch (productException $exception) {
            throw new importException($exception->getMessage());
        }
    }

    /**
     * Find item
     * @param ImportItemModel $item
     * @return bool
     */
    protected function findItem(ImportItemModel $item)
    {
        return ($this->productRepository->findByShopProductId($item->shops[0]['shop_id'], (string)$item->id));
    }


    /**
     * Prepare for render
     * @param ImportItemModel $item
     * @return array
     */
    protected function prepareItem(ImportItemModel $item)
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
//            'categories' => ($item->categories) ? $item->categories[0] : null,
            'price' => ($item->prices) ? (isset($item->prices[0]['price']) ? $item->prices[0]['price'] : 0) : 0,
            'qty' => ($item->stocks) ? (isset($item->stocks[0]['qty']) ? $item->stocks[0]['qty'] : 0) : 0,
        ];
    }

    /**
     * @return ImportItemModel
     */
    public function getItemModel()
    {
        $model = new ImportProductModel();
        return $model;
    }

}
