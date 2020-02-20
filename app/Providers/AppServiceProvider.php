<?php

namespace App\Providers;

use App\Models\Novaposhta\InternetDocument\InternetDocument;
use App\Models\PerAccountsModels\MongoModels\AccountSetting;
use App\Models\PerAccountsModels\MongoModels\DropShipment;
use App\Models\PerAccountsModels\MongoModels\Import;
use App\Models\PerAccountsModels\MongoModels\Moving;
use App\Models\PerAccountsModels\MongoModels\Order;
use App\Models\PerAccountsModels\MongoModels\OrderReturn;
use App\Models\PerAccountsModels\MongoModels\Product;
use App\Models\PerAccountsModels\MongoModels\Shop;
use App\Models\PerAccountsModels\MongoModels\SupplierOrder;
use App\Models\PerAccountsModels\MongoModels\Supply;
use App\Models\PerAccountsModels\MongoModels\WarehouseShipment;
use App\Models\PerAccountsModels\MongoModels\WarehouseAcceptance;
use App\Models\PerAccountsModels\MongoModels\WarehouseShipmentOrder;
use App\Observers\DropShipmentObserver;
use App\Observers\Novaposhta\NovaposhtaInternetDocumentObserver;
use App\Observers\OrderObserver;
use App\Observers\MovingObserver;
use App\Observers\ImportObserver;
use App\Observers\AccountSettingsObserver;
use App\Observers\OrderReturnObserver;
use App\Observers\ProductObserver;
use App\Observers\SupplierOrderObserver;
use App\Observers\SupplyObserver;
use App\Observers\WarehouseAcceptanceObserver;
use App\Observers\WarehouseShipmentOrderObserver;
use App\Repositories\PerAccountsRepositories\CurrencyRepository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Validator::extend('product_supplier_info', 'App\Http\Validators\ProductValidator@validateSupplierInfo');
        \Validator::extend('product_shop', 'App\Http\Validators\ProductValidator@validateShop');
        \Validator::extend('product_stock', 'App\Http\Validators\ProductValidator@validateStock');
        \Validator::extend('product_price', 'App\Http\Validators\ProductValidator@validatePrice');
        \Validator::extend('product_attribute', 'App\Http\Validators\ProductValidator@validateAttribute');
        \Validator::extend('permission_array', 'App\Http\Validators\PermissionValidator@validateArray');
        \Validator::extend('module_array', 'App\Http\Validators\ModuleValidator@validateArray');
        \Validator::extend('backward_delivery', 'App\Http\Validators\InternetDocumentValidator@validateBackwardDelivery');
        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
        Moving::observe(MovingObserver::class);
        SupplierOrder::observe(SupplierOrderObserver::class);
        Supply::observe(SupplyObserver::class);
        OrderReturn::observe(OrderReturnObserver::class);
        DropShipment::observe(DropShipmentObserver::class);
        WarehouseShipmentOrder::observe(WarehouseShipmentOrderObserver::class);
        WarehouseAcceptance::observe(WarehouseAcceptanceObserver::class);
        Import::observe(ImportObserver::class);
        AccountSetting::observe(AccountSettingsObserver::class);
        InternetDocument::observe(NovaposhtaInternetDocumentObserver::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Services\Account\Contracts\AccountManagementServiceInterface',
            'App\Services\Account\AccountManagementService');
        $this->app->bind('App\Services\Account\Contracts\AccountPlanServiceInterface',
            'App\Services\Account\AccountPlanService');
        $this->app->bind('App\Services\Account\Contracts\AccountCreateServiceInterface',
            'App\Services\Account\AccountCreateService');
        $this->app->bind('App\Services\Account\Contracts\AccountDatabaseServiceInterface',
            'App\Services\Account\AccountDatabaseService');

        $this->app->bind('App\Services\User\Contracts\UserCreateServiceInterface',
            'App\Services\User\UserCreateService');

        $this->app->bind('App\Services\Registration\Contracts\RegistrationServiceInterface',
            'App\Services\Registration\RegistrationService');
        $this->app->bind('App\Services\User\Contracts\UserLoginServiceInterface',
            'App\Services\User\UserLoginService');
        $this->app->bind('App\Services\User\Contracts\UserPasswordChangeServiceInterface',
            'App\Services\User\UserPasswordChangeService');
        $this->app->bind('App\Services\User\Contracts\UserPermissionServiceInterface',
            'App\Services\User\UserPermissionService');
        $this->app->bind('App\Services\User\Contracts\UserProfileServiceInterface',
            'App\Services\User\UserProfileService');

        $this->app->bind('App\Services\System\Contracts\SystemPlanServiceInterface',
            'App\Services\System\SystemPlanService');
        $this->app->bind('App\Services\System\Contracts\SystemModuleServiceInterface',
            'App\Services\System\SystemModuleService');
        $this->app->bind('App\Services\System\Contracts\SystemLogServiceInterface',
            'App\Services\System\SystemLogService');
        $this->app->bind('App\Services\System\Contracts\SystemPermissionServiceInterface',
            'App\Services\System\SystemPermissionService');
        $this->app->bind('App\Services\System\Contracts\SystemPaymentServiceInterface',
            'App\Services\System\SystemPaymentService');
        $this->app->bind('App\Services\System\Contracts\SystemAdditionalServicesServiceInterface',
            'App\Services\System\SystemAdditionalServicesService');
        $this->app->bind('App\Services\System\Contracts\SystemNotificationServiceInterface',
            'App\Services\System\SystemNotificationService');

        $this->app->when(\App\Services\System\SystemLiqPayService::class)
            ->needs(\LiqPay::class)
            ->give(function () {
                return new \LiqPay(config('liqpay')['public_key'], config('liqpay')['private_key']);
            });

        $this->app->bind('App\Services\System\Contracts\SystemLiqPayServiceInterface',
            'App\Services\System\SystemLiqPayService');



        $this->app->bind('system_log', 'App\Services\System\SystemLogService');
        $this->app->bind('delivery', 'App\Services\Delivery\DeliveryService');
        $this->app->bind('calculate_rate', 'App\Services\Currency\CalculateRateService');

//        if ($this->app->environment() !== 'production') {
//            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
//        }

        $this->app->bind('App\Services\Product\Contracts\ProductCreateServiceInterface',
            'App\Services\Product\ProductCreateService');

        $this->app->bind('App\Services\Product\Contracts\ProductUpdateServiceInterface',
            'App\Services\Product\ProductUpdateService');

        $this->app->bind('App\Services\Import\Contracts\ImportServiceInterface',
            'App\Services\Import\ImportService');
        $this->app->bind('App\Services\Import\Contracts\ImportProductServiceInterface',
            'App\Services\Import\ImportProductService');
        $this->app->bind('App\Services\Import\Contracts\ImportAttributeServiceInterface',
            'App\Services\Import\ImportAttributeService');
        $this->app->bind('App\Services\Import\Contracts\ImportStrategyInterface',
            'App\Services\Import\ImportStrategy');

        $this->app->singleton('App\Services\Import\Contracts\StorageRepositoryInterface',
            'App\Services\Import\StorageRepository');

        $this->app->when('App\Services\Import\ImportAttributeService')
            ->needs('App\Services\Import\Contracts\StorageRepositoryInterface')
            ->give('App\Services\Import\StorageAttributeRepository');

        $this->app->when('App\Services\Import\Classes\Attribute\ImportMagento')
            ->needs('App\Services\Import\Contracts\StorageRepositoryInterface')
            ->give('App\Services\Import\StorageAttributeRepository');

        $this->app->bind('App\Services\Import\Contracts\StorageInterface',
            'App\Services\Import\Storages\RedisStorage');

        $this->app->bind('App\Services\Image\Contracts\ImageServiceInterface',
            'App\Services\Image\ImageService');

        $this->app->when('App\Http\Controllers\PriceList\SupplierPriceListController')
            ->needs('App\Services\PriceList\Contracts\PriceListServiceInterface')
            ->give('App\Services\PriceList\SupplierPriceListService');

        $this->app->when('App\Http\Controllers\PriceList\SelfPriceListController')
            ->needs('App\Services\PriceList\Contracts\PriceListServiceInterface')
            ->give('App\Services\PriceList\PriceListService');

        $this->app->when('App\Http\Controllers\PriceList\SelfStockController')
            ->needs('App\Services\PriceList\Contracts\PriceListServiceInterface')
            ->give('App\Services\PriceList\SelfStockService');

        // product embeds
        $this->app->bind('App\Services\Product\Contracts\ProductAttributeServiceInterface',
            'App\Services\Product\ProductAttributeService');
        $this->app->bind('App\Services\Product\Contracts\ProductCategoryServiceInterface',
            'App\Services\Product\ProductCategoryService');
        $this->app->bind('App\Services\Product\Contracts\ProductImageServiceInterface',
            'App\Services\Product\ProductImageService');
        $this->app->bind('App\Services\Product\Contracts\ProductPriceServiceInterface',
            'App\Services\Product\ProductPriceService');
        $this->app->bind('App\Services\Product\Contracts\ProductShopServiceInterface',
            'App\Services\Product\ProductShopService');
        $this->app->bind('App\Services\Product\Contracts\ProductStockServiceInterface',
            'App\Services\Product\ProductStockService');
        $this->app->bind('App\Services\Product\Contracts\ProductSupplierServiceInterface',
            'App\Services\Product\ProductSupplierService');

        $this->app->bind('App\Services\Order\Contracts\OrderServiceInterface',
            'App\Services\Order\OrderService');
        $this->app->bind('App\Services\Order\Contracts\OrderProductServiceInterface',
            'App\Services\Order\OrderProductService');
        $this->app->bind('App\Services\Order\Contracts\OrderHistoryServiceInterface',
            'App\Services\Order\OrderHistoryService');
        $this->app->bind('App\Services\Order\Contracts\OrderCustomerServiceInterface',
            'App\Services\Order\OrderCustomerService');
        $this->app->bind('App\Services\Order\Contracts\OrderShipmentServiceInterface',
            'App\Services\Order\OrderShipmentService');

        $this->app->bind('App\Services\DropShipment\Contracts\DropShipmentServiceInterface',
            'App\Services\DropShipment\DropShipmentService');
        $this->app->bind('App\Services\WarehouseShipment\Contracts\WarehouseShipmentServiceInterface',
            'App\Services\WarehouseShipment\WarehouseShipmentService');
        $this->app->bind('App\Services\WarehouseShipment\Contracts\WarehouseShipmentOrderServiceInterface',
            'App\Services\WarehouseShipment\WarehouseShipmentOrderService');
        $this->app->bind('App\Services\WarehouseAcceptance\Contracts\WarehouseAcceptanceServiceInterface',
            'App\Services\WarehouseAcceptance\WarehouseAcceptanceService');
        $this->app->bind('App\Services\Warehouse\Contracts\WarehouseServiceInterface',
            'App\Services\Warehouse\WarehouseService');

        $this->app->bind('App\Services\Shop\Contracts\ShopServiceInterface',
            'App\Services\Shop\ShopService');
        $this->app->bind('App\Services\DropShipment\Contracts\ConfirmTokenServiceInterface',
            'App\Services\DropShipment\ConfirmTokenService');
        $this->app->bind('App\Services\Supply\Contracts\ConfirmTokenServiceInterface',
        'App\Services\Supply\ConfirmTokenService');

        $this->app->bind('App\Services\Supplier\Contracts\SupplierServiceInterface',
            'App\Services\Supplier\SupplierService');


        //Settings
        $this->app->bind('App\Services\Settings\TrafficLight\Contracts\TrafficLightServiceInterface',
            'App\Services\Settings\TrafficLight\TrafficLightService');
        $this->app->bind('App\Services\Settings\PriceType\Contracts\PriceTypeServiceInterface',
            'App\Services\Settings\PriceType\PriceTypeService');
        $this->app->bind('App\Services\Settings\Api\Contracts\AccountSettingsServiceInterface',
            'App\Services\Settings\Api\AccountSettingsService');

        //Export
        $this->app->bind('export', 'App\Services\Export\ExportFactory');
        $this->app->bind('App\Services\Shop\Contracts\ShopExportServiceInterface',
            'App\Services\Shop\ShopExportService');
        $this->app->bind('App\Services\Export\Contracts\ExportServiceInterface',
            'App\Services\Export\ExportService');

        $this->app->bind('App\Services\SupplierOrder\Contracts\SupplierOrderServiceInterface',
            'App\Services\SupplierOrder\SupplierOrderService');
        $this->app->bind('App\Services\SupplierOrder\Contracts\SupplierOrderProductServiceInterface',
            'App\Services\SupplierOrder\SupplierOrderProductService');
        $this->app->bind('App\Services\SupplierOrder\Contracts\SupplierOrderProductServiceInterface',
            'App\Services\SupplierOrder\SupplierOrderProductService');


        //supply
        $this->app->bind('App\Services\Supply\Contracts\SupplyServiceInterface',
            'App\Services\Supply\SupplyService');
        $this->app->bind('App\Services\Supply\Contracts\SupplyProductServiceInterface',
            'App\Services\Supply\SupplyProductService');
        $this->app->bind('App\Services\Supply\Contracts\SupplyProductShipmentServiceInterface',
            'App\Services\Supply\SupplyProductShipmentService');


        $this->app->bind('App\Services\OrderReturn\Contracts\OrderReturnServiceInterface',
            'App\Services\OrderReturn\OrderReturnService');
        $this->app->bind('App\Services\OrderReturn\Contracts\OrderReturnProductServiceInterface',
            'App\Services\OrderReturn\OrderReturnProductService');
        $this->app->bind('App\Services\OrderReturn\Contracts\OrderReturnProductServiceInterface',
            'App\Services\OrderReturn\OrderReturnProductService');


        //customer
        $this->app->bind('App\Services\Customer\Contracts\CustomerAttributeServiceInterface',
            'App\Services\Customer\CustomerAttributeService');


        //event notification
        $this->app->bind('App\Services\Notifications\Contracts\EventNotificationServiceInterface',
            'App\Services\Notifications\EventNotificationsService');

        //notification services
        $this->app->bind('App\Services\Notifications\Contracts\TurboSmsInterface',
            'App\Services\Notifications\TurboSmsService');


        //feedback services
        $this->app->bind('App\Services\Notifications\Contracts\HelpdeskeddyInterface',
            'App\Services\Notifications\HelpdeskeddyService');

        $this->app->bind('App\Services\Ticket\Contracts\TicketServiceInterface',
            'App\Services\Ticket\TicketService');

        //moving
        $this->app->bind('App\Services\Moving\Contracts\MovingServiceInterface',
            'App\Services\Moving\MovingService');
        $this->app->bind('App\Services\Moving\Contracts\MovingProductServiceInterface',
            'App\Services\Moving\MovingProductService');
        $this->app->bind('App\Services\Moving\Contracts\MovingProductShipmentServiceInterface',
            'App\Services\Moving\MovingProductShipmentService');
        $this->app->bind('App\Services\Moving\Contracts\MovingProductAcceptanceServiceInterface',
            'App\Services\Moving\MovingProductAcceptanceService');






        //binotel
        $this->app->bind('App\Services\Binotel\Contracts\BinotelServiceInterface',
            'App\Services\Binotel\BinotelService');

        $this->app->bind('App\Services\Currency\Contracts\CurrencyServiceInterface',
            'App\Services\Currency\CurrencyService');

        $this->app->bind('App\Services\Currency\Contracts\CurrencyRateServiceInterface',
            'App\Services\Currency\CurrencyRateService');
    }
}
