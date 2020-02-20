<?php
namespace App\Services\Import\Classes\Product;

use App\Services\Import\Classes\BaseImportClass;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ProductRepositoryInterface;
use App\Services\Integration\PrestashopIntegration;

class ImportPrestashop extends BaseImportClass implements ImportImplementationInterface
{
    const LIMIT = 10;

    public function prepare($importId)
    {
        parent::prepare($importId);

        $offset = 0;

        while (count($products = $this->getProducts($offset)) > 0) {
            foreach ($products as $product) {

                if ($product->state == 0) {
                    continue;
                }

                $model = $this->storage->getItemModel();

                $model->name = $this->handleNameColumn($product);
                $model->sku = $this->handleSkuColumn($product);
                $model->status = ProductRepositoryInterface::STATUS_ACTIVE;
                $model->type = ProductRepositoryInterface::TYPE_SINGLE;
                $model->description = $this->handleDescriptionColumn($product);
                $model->volume = $this->handleVolumeColumn($product);
                $model->weight = $this->handleWeightColumn($product);
                $model->unit = ProductRepositoryInterface::UNIT_PIECE;
                $model->prices = $this->handlePricesColumn($product);
                $model->stocks = $this->handleStocksColumn($product);
                $model->shops = $this->handleShopsColumn($product);
                $model->images = $this->handleImagesColumn($product);

                $this->storage->pushDirty($model);
            }

            $offset += self::LIMIT;
        }
    }

    /**
     * @param array $offset
     */
    private function getProducts($offset)
    {
        return $this->integration->product()->getAll($offset);
    }

    private function handleVolumeColumn($product)
    {
        return sprintf(
            '%dx%dx%d:%s',
            $product->width,
            $product->height,
            $product->depth,
            ProductRepositoryInterface::VOLUME_UNIT_SM
        );
    }

    private function handleWeightColumn($product)
    {
        return sprintf('%s:%s', $product->weight, ProductRepositoryInterface::WEIGHT_UNIT_KG);
    }

    private function handleNameColumn($product)
    {
        return $this->processMultilangValue($product->name);
    }

    public function handleSkuColumn($product)
    {
        return is_string($product->reference) ? $product->reference : '';
    }

    private function handleDescriptionColumn($product)
    {
        return $this->processMultilangValue($product->description);
    }

    private function handlePricesColumn($product)
    {
        return [
            [
                'price_type_id' => $this->shop->price_type_id,
                'price' => (float) $product->price,
                'currency_id' => $this->shop->currency_id,
            ]
        ];
    }

    private function handleStocksColumn($product)
    {
        if (!count($product->associations->stock_availables)) {
            return 0;
        }

        return [
            [
                'warehouse_id' =>  $this->import->import_warehouse_id,
                'qty' => $product->quantity,
            ]
        ];
    }

    private function handleShopsColumn($product)
    {
        return [
            [
                'shop_id' => $this->shop->_id,
                'shop_product_id' => (string) $product->id,
                'shop_product_url' => sprintf(
                    '%s%d-%s.html',
                    $this->shop->settings->url,
                    $product->id,
                    $this->processMultilangValue($product->link_rewrite)
                ),
                'shop_sku' => $product->reference,
                'price_type_id' => $this->shop->price_type_id,
                'import_id' => $this->importId,
                'active' => true,
            ]
        ];
    }

    public function handleImagesColumn($product) : array
    {
        $result = [];

        if (isset($product->associations->images)) {
            foreach ($product->associations->images as $image) {
                $result[] = [
                    'path' => null,
                    'url' => sprintf(
                        '%sapi/images/products/%d/%d',
                        $this->shop->settings->url,
                        $product->id,
                        $image->id
                    )
                ];
            }
        }

        return $result;
    }

    private function processMultilangValue(array $value)
    {
        return $value[0]->value;
    }
}