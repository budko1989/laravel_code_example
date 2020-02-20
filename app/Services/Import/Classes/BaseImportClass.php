<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 1/15/18
 * Time: 5:43 PM
 */

namespace App\Services\Import\Classes;

use App\Repositories\PerAccountsRepositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\CustomerRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ImportRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use App\Services\Import\Contracts\StorageRepositoryInterface;
use App\Services\Integration\Contracts\IntegrationInterface;
use App\Services\Integration\Contracts\IntegrationServiceInterface;

class BaseImportClass
{
    /**
     * @var \App\Services\Import\StorageRepository | \App\Services\Import\StorageAttributeRepository
     */
    public $storage;

    /**
     * @var \App\Repositories\PerAccountsRepositories\ImportRepository
     */
    public $importRepository;

    /**
     * @var \App\Repositories\PerAccountsRepositories\CategoryRepository
     */
    public $categoryRepository;

    /**
     * @var \App\Repositories\PerAccountsRepositories\ProductRepository
     */
    public $productRepository;

    /**
     * @var \App\Repositories\PerAccountsRepositories\CustomerRepository
     */
    public $customerRepository;

    /**
     * @var \App\Services\Integration\IntegrationService
     */
    private $integrationService;

    /**
     * @var IntegrationInterface
     */
    public $integration;

    /**
     * @var string
     */
    public $importId;

    /**
     * @var \App\Models\PerAccountsModels\MongoModels\Import
     */
    public $import;

    /**
     * @var \App\Models\PerAccountsModels\MongoModels\Shop
     */
    public $shop;

    public function __construct(
        StorageRepositoryInterface $storage,
        ImportRepositoryInterface $importRepository,
        IntegrationServiceInterface $integrationService,
        CategoryRepositoryInterface $categoryRepository,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository
    )
    {
        $this->storage = $storage;
        $this->importRepository = $importRepository;
        $this->integrationService = $integrationService;
        $this->categoryRepository = $categoryRepository;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
    }

    public function prepare($importId)
    {
        $this->importId = $importId;
        $this->import = $this->importRepository->findBy('_id', $this->importId);
        $this->shop = $this->import->shop;
        $this->integration = $this->integrationService->setShop($this->shop->_id);
    }


}