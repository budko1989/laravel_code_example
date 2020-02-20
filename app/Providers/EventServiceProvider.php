<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [

        'App\Events\Account\CreateAccountDBs' => [
            'App\Listeners\Account\CreateAccountDBsListener',
        ],
        'App\Events\Account\SpendingTariffPaymentEvent' => [
            'App\Listeners\Account\SpendingTariffPaymentListener',
            'App\Listeners\Account\SpendingTariffPaymentSupportListener'
        ],

        'App\Events\Import\BeforeLoadData' => [
            'App\Listeners\Import\ImportMagentoAttributesBeforeLoadListener',
        ],
        'App\Events\Import\LoadData' => [
            'App\Listeners\Import\LoadDataListener',
        ],
        'App\Events\Import\AfterLoadData' => [
            'App\Listeners\Import\SetLastImportOrderDateAfterLoadListener',
        ],
        'App\Events\Import\StartValidate' => [
            'App\Listeners\Import\StartValidateListener',
        ],
        'App\Events\Import\BeforeValidate' => [],
        'App\Events\Import\AfterValidate' => [],
        'App\Events\Import\BeforeImport' => [],
        'App\Events\Import\ImportProgress' => [],
        'App\Events\Import\AfterImport' => [
            'App\Listeners\Import\SetM2MParentForEmbed'
        ],
        'App\Events\Import\ImportItems' => [
            'App\Listeners\Import\ImportItemsListener',
        ],
        'App\Events\Import\ChangeImportStatus' => [
            'App\Listeners\Import\ImportStatusListener',
        ],
        'App\Events\Product\DownloadImage' => [
            'App\Listeners\Product\DownloadImageListener',
        ],
        'App\Events\Product\SyncShop' => [
            'App\Listeners\Product\SyncShopListener',
        ],
        'App\Events\Order\ChangeStatus' => [
            'App\Listeners\Order\SyncOrderStatusInShopListener',
            'App\Listeners\Order\AddHistoryListener',
            'App\Listeners\Order\CheckOverdueListener',
            'App\Listeners\Order\NotifyCustomerOnOrderChangeStatus',
            'App\Listeners\Order\CreateWarehouseOrdersListener',
            'App\Listeners\Order\CreateDropshipmentsListener',
        ],
        'App\Events\Order\OrderCreated' => [
            'App\Listeners\Order\OrderCreatedListener',
        ],
        'App\Events\Order\OrderUpdated' => [
            'App\Listeners\Order\OrderUpdatedListener',
        ],
        'App\Events\Order\UpdateOrderFormationPercent' => [
            'App\Listeners\Order\UpdateOrderFormationPercentListener',
        ],
        'App\Events\TrafficLight\OverdueDocument' => [
            'App\Listeners\Order\OverdueOrderListener',
        ],
        'App\Events\Order\StoreShipmentInfo' => [
            'App\Listeners\Order\IncreaseProductReserveListener',
            'App\Listeners\Order\StoreShipmentInfoListener',
        ],
        'App\Events\Order\DeleteShipmentInfo' => [
            'App\Listeners\Order\DecreaseProductReserveListener',
        ],
        'App\Events\DropShipment\ChangeStatus' => [
            'App\Listeners\DropShipment\ChangeOrderProductStatusListener',
            'App\Listeners\DropShipment\RefreshOrderPercentCountersListener',
        ],
        'App\Events\WarehouseShipment\ChangeStatus' => [
            // 'App\Listeners\WarehouseShipment\ChangeOrderProductStatusListener',
            'App\Listeners\WarehouseShipment\ChangeOrderProductStockListener',
            'App\Listeners\WarehouseShipment\ChangeMovingStatusListener',
            'App\Listeners\WarehouseShipment\RefreshOrderPercentCountersListener',
            'App\Listeners\WarehouseShipment\SetWarehouseShipmentDeferred',
        ],
        'App\Events\WarehouseShipment\ChangeOrderStatus' => [
            'App\Listeners\WarehouseShipment\UpdateWarehouseShipmentStatusListener',
        ],
        'App\Events\WarehouseAcceptance\ChangeStatus' => [
            'App\Listeners\WarehouseAcceptance\ChangeOrderProductStockListener',
            'App\Listeners\WarehouseAcceptance\ChangeMovingStatusListener',
            'App\Listeners\WarehouseAcceptance\ChangeSupplyStatusListener',
        ],
        'App\Events\Moving\ChangeStatus' => [
            'App\Listeners\Moving\RefreshOrderPercentCountersListener',
            'App\Listeners\Moving\CreateWarehouseOrdersListener',
        ],
        'App\Events\Supply\ChangeStatus' => [
            'App\Listeners\Supply\RefreshOrderPercentCountersListener',
            'App\Listeners\Supply\SupplyChangeStatusListener',
        ],
        'App\Events\DropShipment\NotifySupplier' => [
            'App\Listeners\DropShipment\NotifySupplierListener',
        ],
        'App\Events\Sync\CheckNewOrders' => [
            'App\Listeners\Sync\CheckNewOrdersListener',
        ],
        'App\Events\Product\MultipleProductAddToShop' => [
            '\App\Listeners\Product\MultipleProductAddToShopListener'
        ],
        'App\Events\Sync\TestEvent' => [
            'App\Listeners\Sync\TestEventListener',
        ],
        'App\Events\Export\StartExport' => [
            'App\Listeners\Export\StartExportListener',
        ],
        'App\Events\System\AdminNotificationEvent' => [
            'App\Listeners\System\AdminNotificationListener',
        ],
        'App\Events\Novaposhta\NewApiKeySync' => [
            'App\Listeners\Novaposhta\NewApiKeyListener',
        ],
        'App\Events\Currency\ChangeRate' => [
            'App\Listeners\Currency\AddHistoryListener',
        ],

    ];

    /**
     * Class event subscribers.
     *
     * @var array
     */
    protected $subscribe = [
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {

        parent::boot();

    }
}
