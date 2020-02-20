<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 1/12/18
 * Time: 1:06 PM
 */

namespace App\Services\Import;


use App\Exceptions\importException;
use App\Exceptions\orderException;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\OrderProductRequest;
use App\Repositories\PerAccountsRepositories\Contracts\ImportErrorRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ImportItemRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ImportRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\OrderRepositoryInterface;
use App\Services\Import\Contracts\ImportOrderServiceInterface;
use App\Services\Import\Contracts\StorageRepositoryInterface;
use App\Services\Import\Models\ImportItemModel;
use App\Services\Import\Models\ImportOrderModel;
use App\Services\Order\Contracts\OrderCustomerServiceInterface;
use App\Services\Order\Contracts\OrderProductServiceInterface;
use App\Services\Order\Contracts\OrderServiceInterface;
use Illuminate\Validation\ValidationException;
use App\Repositories\PerAccountsRepositories\Contracts\ShopRepositoryInterface;
use App\Facades\Delivery;

class ImportOrderService extends BaseImportService implements ImportOrderServiceInterface
{

    /**
     * @var \App\Repositories\PerAccountsRepositories\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \App\Services\Order\OrderService
     */
    private $orderService;

    /**
     * @var \App\Services\Order\OrderProductService
     */
    private $orderProductService;

    /**
     * @var \App\Services\Order\OrderCustomerService
     */
    private $orderCustomerService;

    public function __construct(
        ImportRepositoryInterface $importRepository,
        ImportErrorRepositoryInterface $importErrorRepository,
        ImportItemRepositoryInterface $importItemRepository,
        StorageRepositoryInterface $storageRepository,
        OrderRepositoryInterface $orderRepository,
        OrderServiceInterface $orderService,
        OrderProductServiceInterface $orderProductService,
        OrderCustomerServiceInterface $orderCustomerService,
        ShopRepositoryInterface $shopRepository
    )
    {
        parent::__construct($importRepository, $importErrorRepository, $importItemRepository, $storageRepository, $shopRepository);
        $this->orderRepository = $orderRepository;
        $this->orderService = $orderService;
        $this->orderProductService = $orderProductService;
        $this->orderCustomerService = $orderCustomerService;
    }

    /**
     * Validate item or throw ValidationException
     * @param ImportItemModel $item
     * @throws ValidationException
     * @return void
     */
    protected function validateItem(ImportItemModel $item)
    {
        /**
         * @var $item \App\Services\Import\Models\ImportOrderModel
         */
        \Validator::validate($item->toArray(), CreateOrderRequest::rules());
        foreach ($item->products as $product) {
            \Validator::validate($product, OrderProductRequest::rules());
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
         * @var $item \App\Services\Import\Models\ImportOrderModel
         */
        try {
            $model = $this->orderService->create([
                'external_id' => (string)$item->id,
                'order_date' => $item->order_date,
                'shop_id' => $item->shop_id,
                'customer_id' => $item->customer_id,
//                'delivery_cost' =>  $item->fields['delivery_cost'],
                'delivery_info' => $item->delivery_info,
                'payment_info' => $item->payment_info,
                'comment' => $item->comment,
                'payment_type' => (int)($item->payment_type ?? 0),
                'payment_status_id' => OrderRepositoryInterface::PAYMENT_STATUS_NOT_PAID,
            ]);

            foreach ($item->products as $product) {
                $this->orderProductService->add($model, $product);
            }
            if (isset($item->delivery_type)) {
//                Delivery::addToOrder($model, ['delivery_type' => $item->delivery_type, 'fields' => ['delivery_cost' => 0]]);
                Delivery::addToOrder($model, ['delivery_type' => $item->delivery_type, 'delivery_service' => isset($item->delivery_service) ? $item->delivery_service : '', 'delivery_cost' =>  isset($item->delivery_cost) ? $item->delivery_cost : 0, 'fields'  => isset($item->fields) ? $item->fields : []]);

            }
//            $this->orderCustomerService->add($model, $item->customer_id);
        } catch (orderException $exception) {
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
        /**
         * @var $item \App\Services\Import\Models\ImportOrderModel
         */
        return ($this->orderRepository->findOneByExternalId($item->shop_id, (string)$item->id));
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
//            'name' => $item->name,
//            'sku' => $item->sku,
////            'categories' => ($item->categories) ? $item->categories[0] : null,
//            'price' => ($item->prices) ? $item->prices[0]['price'] : 0,
//            'qty' => ($item->stocks) ? $item->stocks[0]['qty'] : 0,
        ];
    }

    /**
     * @return ImportItemModel
     */
    public function getItemModel()
    {
        $model = new ImportOrderModel();
        return $model;
    }

}