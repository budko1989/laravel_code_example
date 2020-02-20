<?php
/**
 * Created by PhpStorm.
 * User: nastia
 * Date: 31.10.17
 * Time: 14:49
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoriesServiceProvider extends ServiceProvider
{

    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind('App\Repositories\Contracts\AccountRepositoryInterface',
            'App\Repositories\AccountRepository');
        $this->app->bind('App\Repositories\Contracts\RoleRepositoryInterface',
            'App\Repositories\RoleRepository');
        $this->app->bind('App\Repositories\Contracts\UserRepositoryInterface',
            'App\Repositories\UserRepository');
        $this->app->bind('App\Repositories\Contracts\LogRepositoryInterface',
            'App\Repositories\LogRepository');
        $this->app->bind('App\Repositories\Contracts\ConfirmTokenRepositoryInterface',
            'App\Repositories\ConfirmTokenRepository');

        //System

        $this->app->bind('App\Repositories\Contracts\RefillRepositoryInterface',
            'App\Repositories\RefillRepository');
        $this->app->bind('App\Repositories\Contracts\SpendingRepositoryInterface',
            'App\Repositories\SpendingRepository');
        $this->app->bind('App\Repositories\Contracts\PlanRepositoryInterface',
            'App\Repositories\PlanRepository');
        $this->app->bind('App\Repositories\Contracts\PlanManagementRepositoryInterface',
            'App\Repositories\PlanManagementRepository');
        $this->app->bind('App\Repositories\Contracts\ModuleRepositoryInterface',
            'App\Repositories\ModuleRepository');
        $this->app->bind('App\Repositories\Contracts\PermissionRepositoryInterface',
            'App\Repositories\PermissionRepository');
        $this->app->bind('App\Repositories\Contracts\AdditionalServicesRepositoryInterface',
            'App\Repositories\AdditionalServicesRepository');


        //Per accounts database models repositories

        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\BackupRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\BackupRepository');

        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\CategoryRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\CategoryRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ProductRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ImportErrorRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ImportErrorRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ImportItemRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ImportItemRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ImportRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ImportRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\PriceTypeRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\PriceTypeRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\CustomAttributeRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\CustomAttributeRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\CustomAttributeValueRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\CustomAttributeValueRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ShopRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ShopRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\SupplierRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\SupplierRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\WarehouseRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\WarehouseRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\TableSettingRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\TableSettingRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\AccountSettingsRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\AccountSettingsRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\EventNotificationRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\EventNotificationRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\CurrencyRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\CurrencyRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\CurrencyRateRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\CurrencyRateRepository');


        // product embeds
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ProductPriceRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ProductPriceRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ProductImageRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ProductImageRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ProductAttributeRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ProductAttributeRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ProductShopRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ProductShopRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ProductSupplierRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ProductSupplierRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ProductStockRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ProductStockRepository');


        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\CustomerRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\CustomerRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\CallRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\CallRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\OrderRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\OrderRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\OrderProductRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\OrderProductRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\OrderHistoryRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\OrderHistoryRepository');

        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\DropShipmentRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\DropShipmentRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\WarehouseShipmentRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\WarehouseShipmentRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\WarehouseShipmentOrderRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\WarehouseShipmentOrderRepository');

        //Settings
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\TrafficLightRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\TrafficLightRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\CanceledReasonRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\CanceledReasonRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\OrderSourceRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\OrderSourceRepository');

        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\PriceListRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\PriceListRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ShopExportRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ShopExportRepository');

        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\SupplierOrderRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\SupplierOrderRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\SupplierOrderProductRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\SupplierOrderProductRepository');

        //Supply
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\SupplyRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\SupplyRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\SupplyProductRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\SupplyProductRepository');

        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\OrderReturnRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\OrderReturnRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\OrderReturnProductRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\OrderReturnProductRepository');

        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\WarehouseAcceptanceRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\WarehouseAcceptanceRepository');

        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\IntegrationRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\IntegrationRepository');

        //tickets
        $this->app->bind('App\Repositories\Contracts\TicketRepositoryInterface',
            'App\Repositories\TicketRepository');
        $this->app->bind('App\Repositories\Contracts\TicketFileRepositoryInterface',
            'App\Repositories\TicketFileRepository');
        $this->app->bind('App\Repositories\Contracts\TicketChattingHistoryRepositoryInterface',
            'App\Repositories\TicketChattingHistoryRepository');

        //moving
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\MovingRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\MovingRepository');
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\MovingProductRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\MovingProductRepository');

        //supplier
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\SupplierImportSettingRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\SupplierImportSettingRepository');

        //shop options
        $this->app->bind('App\Repositories\PerAccountsRepositories\Contracts\ShopOptionsOpencartRepositoryInterface',
            'App\Repositories\PerAccountsRepositories\ShopOptionsOpencartRepository');

    }
}