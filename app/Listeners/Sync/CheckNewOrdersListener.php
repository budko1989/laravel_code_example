<?php

namespace App\Listeners\Sync;

use App\Events\Import\LoadData;
use App\Events\Sync\CheckNewOrders;
use App\Exceptions\integrationException;
use App\Repositories\PerAccountsRepositories\Contracts\ShopRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ImportRepositoryInterface;
use App\Services\Integration\Contracts\IntegrationServiceInterface;
use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckNewOrdersListener
{

    /**
     * @var \App\Services\Integration\IntegrationService
     */
    private $integrationService;

    /**
     * @var \App\Repositories\PerAccountsRepositories\ShopRepository
     */
    private $shopRepository;

    /**
     * @var \App\Repositories\PerAccountsRepositories\ImportRepository
     */
    private $importRepository;

    /**
     * Create the event listener.
     *
     * @param $integrationService IntegrationServiceInterface
     * @param $importRepository ImportRepositoryInterface
     * @param $shopRepository ShopRepositoryInterface
     */
    public function __construct(
        ShopRepositoryInterface $shopRepository,
        ImportRepositoryInterface $importRepository,
        IntegrationServiceInterface $integrationService
    )
    {
        $this->shopRepository = $shopRepository;
        $this->integrationService = $integrationService;
        $this->importRepository = $importRepository;
    }

    /**
     * Handle the event.
     *
     * @param  CheckNewOrders  $event
     * @return void
     */
    public function handle(CheckNewOrders $event)
    {
        /**
         * @var $shop \App\Models\PerAccountsModels\MongoModels\Shop
         */
        $shop = $this->shopRepository->find($event->shopId);
        try {
            if ($this->integrationService->setShop($shop->_id)->order()->hasNew()) {
                /**
                 * @var $import \App\Models\PerAccountsModels\MongoModels\Import
                 */
                $import = $this->importRepository->createImport([
                    'shop_id' => $shop->_id,
                    'type' => ImportRepositoryInterface::TYPE_ORDER,
                    'import_without_confirmation' => true,
                ]);
                event(new LoadData($import->_id, \Auth::user()->id));
            }
        } catch (integrationException $exception) {
            \SystemLog::error($exception);
        }
    }
}
