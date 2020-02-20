<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/27/17
 * Time: 12:25 PM
 */

namespace App\Services\Import;

use App\Events\Import\AfterImport;
use App\Events\Import\AfterValidate;
use App\Events\Import\BeforeImport;
use App\Events\Import\BeforeValidate;
use App\Events\Import\ImportProgress;
use App\Models\PerAccountsModels\MongoModels\Import;
use App\Repositories\PerAccountsRepositories\Contracts\ImportErrorRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ImportItemRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ImportRepositoryInterface;
use App\Repositories\PerAccountsRepositories\Contracts\ShopRepositoryInterface;
use App\Services\Import\Contracts\BaseImportInterface;
use App\Services\Import\Contracts\ImportImplementationInterface;
use App\Services\Import\Contracts\ImportStrategyInterface;
use App\Services\Import\Contracts\StorageRepositoryInterface;
use App\Services\Import\Models\ImportItemModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use App\Exceptions\importException;
use App\Facades\SystemLog;
use Carbon\Carbon;

abstract class BaseImportService implements BaseImportInterface
{

    /**
     * @var \App\Repositories\PerAccountsRepositories\ImportRepository
     */
    public $importRepository;

    /**
     * @var \App\Repositories\PerAccountsRepositories\ImportErrorRepository
     */
    public $importErrorRepository;

    /**
     * @var \App\Repositories\PerAccountsRepositories\ImportItemRepository
     */
    public $importItemRepository;

    /**
     * @var StorageRepository
     */
    private $storageRepository;


    /**
     * @var \App\Repositories\PerAccountsRepositories\ShopRepository
     */
    private $shopRepository;

    /**
     * Create a new command instance.
     *
     * @param $importRepository ImportRepositoryInterface
     * @param $importErrorRepository ImportErrorRepositoryInterface
     * @param $importItemRepository ImportItemRepositoryInterface
     * @param $storageRepository StorageRepositoryInterface
     */
    public function __construct(
        ImportRepositoryInterface $importRepository,
        ImportErrorRepositoryInterface $importErrorRepository,
        ImportItemRepositoryInterface $importItemRepository,
        StorageRepositoryInterface $storageRepository,
        ShopRepositoryInterface $shopRepository
    )
    {
        $this->importRepository = $importRepository;
        $this->storageRepository = $storageRepository;
        $this->importErrorRepository = $importErrorRepository;
        $this->importItemRepository = $importItemRepository;
        $this->shopRepository = $shopRepository;
    }

    /**
     * Find item
     * @param ImportItemModel $item
     * @return bool
     */
    abstract protected function findItem(ImportItemModel $item);

    /**
     * Validate item or throw ValidationException
     * @param ImportItemModel $item
     * @throws ValidationException
     * @return void
     */
    abstract protected function validateItem(ImportItemModel $item);

    /**
     * Prepare for render
     * @param ImportItemModel $item
     * @return array
     */
    abstract protected function prepareItem(ImportItemModel $item);

    /**
     * Create or update item or throw importException
     * @param ImportItemModel $item
     * @throws importException
     * @return void
     */
    abstract protected function importItem(ImportItemModel $item);

    /**
     * @param string $importId
     * @param string $type
     * @param mixed $messages
     * @param ImportItemModel $item
     */
    public function addImportError(string $importId, string $type, $messages, ImportItemModel $item)
    {
        $this->importErrorRepository->create([
            'import_id' => $importId,
            'type' => $type,
            'item' => $item->toArray(),
            'messages' => $messages,
        ]);
    }

    /**
     * @param string $importId
     * @param ImportItemModel $item
     */

    public function addImportItemLog(string $importId, ImportItemModel $item)
    {
        /**
         * @var $model \App\Models\PerAccountsModels\MongoModels\ImportItem
         */
        $model = $this->importItemRepository->emptyModel();
        $model->import_id = $importId;
        foreach ($item->getAttributes() as $attribute => $value) {
            $model->{$attribute} = $value;
        }
        $this->importItemRepository->createByModel($model);
    }

    /**
     * @return ImportItemModel
     */
    public function getItemModel()
    {
        $model = new ImportItemModel();
        return $model;
    }

    /**
     * @param string $type
     * @param string $name
     * @param string $importId
     * @return ImportImplementationInterface
     */
    final private function startLoad($type, $name, $importId)
    {
        /**
         * @var $strategy ImportStrategyInterface
         */
        $this->storageRepository->setImportId($importId);
        $this->storageRepository->setImportItemModel($this->getItemModel());
        $strategy = \App::makeWith('App\Services\Import\Contracts\ImportStrategyInterface',
            ['type' => ucfirst($type), 'context' => $name, 'importId' => $importId]);
        return $strategy->getWorker();
    }

    /**
     * @param $importId string
     * @param $message string
     * @throws importException
     */
    public function setError($importId, $message = 'system error')
    {
        $this->importRepository->setStatusError($importId, $message);
        throw new importException($message);
    }

    /**
     * @param Import|Model $import
     * @return Import|Model
     * @throws importException
     */
    public function load(Model $import)
    {
         try {
            if ($import->status != $this->importRepository::STATUS_NEW) {
                $this->setError($import->_id, 'OnlyNewImportCanBeLoaded');
            }
            $this->startLoad($import->type, $import->shop->type, $import->_id);
            return $this->importRepository->setStatusLoaded($import->_id);
         } catch (\Exception $exception) {
             $this->setError($import->_id, $exception->getMessage());
             SystemLog::error($exception);
             return $import;
         }
    }

    /**
     * @param Import|Model $import
     * @return Import|Model
     * @throws importException
     */
    public function validate(Model $import)
    {
        try {
            if ($import->status != $this->importRepository::STATUS_LOADED) {
                $this->setError($import->_id, 'OnlyLoadedImportCanBeValidated');
            }
            event(new BeforeValidate($import->_id, \Auth::user()->id));
            $this->importRepository->setStatusValidating($import->_id);
            $this->storageRepository->setImportId($import->_id);
            $dirty = $founded = $validated = $validationErrors = 0;
            while ($item = $this->storageRepository->popDirty()) {
                $dirty++;
                try {
                    if ($this->findItem($item)) {
                        $this->storageRepository->pushFounded($item);
                        $founded++;
                    } else {
                        $this->validateItem($item);
                        $this->storageRepository->pushValidated($item);
                        $validated++;
                    }
                } catch (\Exception $exception) {
                    if ($exception instanceof ValidationException) {
                        $this->addImportError(
                            $import->_id,
                            ImportErrorRepositoryInterface::ERROR_TYPE_VALIDATION,
                            $exception->validator->getMessageBag()->all(),
                            $item);
                        $validationErrors++;
                    }
                    else {
                        $this->setError($import->_id, 'Error by validating item, message: '. $exception->getMessage());
                    }
                }
            }
            $this->importRepository->updateCounters($import->_id, [
                'total' => $dirty,
                'founded' => $founded,
                'validated' => $validated,
                'validationErrors' => $validationErrors
            ]);
            event(new AfterValidate($import, \Auth::user()->id));
            return $this->importRepository->setStatusValidated($import->_id);

        } catch (\Exception $exception) {
            $this->setError($import->_id, $exception->getMessage());
            return $import;
        }
    }

    /**
     * @param Import|Model $import
     * @return \Illuminate\Support\Collection|array
     */
    public function getSkipped(Model $import)
    {
        $this->storageRepository->setImportId($import->_id);
        $items = [];
        foreach ($this->storageRepository->getSkipped() as $item) {
            $items[] = $this->prepareItem($this->getItemModel()->fill(json_decode($item, true)));
        }
        return $items;
    }

    /**
     * @param Import|Model $import
     * @return \Illuminate\Support\Collection|array
     */
    public function getFounded(Model $import)
    {
        $this->storageRepository->setImportId($import->_id);
        $items = [];
        foreach ($this->storageRepository->getFounded() as $item) {
            $items[] = $this->prepareItem($this->getItemModel()->fill(json_decode($item, true)));
        }
        return $items;
    }

    /**
     * @param Import|Model $import
     * @return \Illuminate\Support\Collection|array
     */
    public function getValidated(Model $import)
    {
        $this->storageRepository->setImportId($import->_id);
        $items = [];
        foreach ($this->storageRepository->getValidated() as $item) {
            $items[] = $this->prepareItem($this->getItemModel()->fill(json_decode($item, true)));
        }
        return $items;
    }

    /**
     * @param Import|Model $import
     * @param array $actions
     * @throws importException
     */
    public function import(Model $import, array $actions = [])
    {
        try {
            if ($import->status != $this->importRepository::STATUS_VALIDATED) {
                $this->setError($import->_id, 'OnlyValidatedImportCanBeImported');
            }
            event(new BeforeImport($import, \Auth::user()->id));
            $this->importRepository->setStatusImporting($import->_id);
            $this->storageRepository->setImportId($import->_id);
            $skipped = $imported = $importErrors = $index = $last = 0;
            $total = ($import->import_without_confirmation) ? $import->counters['validated'] : $this->countImportItems($actions);
            $middle = round($total/10);
            while ($item = $this->storageRepository->popValidated()) {
                try {
                    if ($import->import_without_confirmation) {
                        $this->importItem($item);
                        $imported++;
                        $this->addImportItemLog($import->_id, $item);
                    } else {
                        if (isset($actions[$item->id]) && $actions[$item->id] == ImportItemModel::ACTION_IMPORT) {
                            $this->importItem($item);
                            $imported++;
                            $this->addImportItemLog($import->_id, $item);
                        } elseif (isset($actions[$item->id]) && $actions[$item->id] == ImportItemModel::ACTION_SKIPP) {
                            $this->storageRepository->pushSkipped($item);
                            $skipped++;
                        } else {
                            throw new importException('UndefinedImportAction');
                        }
                    }
                } catch (\Exception $exception) {
//                    if($exception instanceof importException) {
                        $this->addImportError(
                            $import->_id,
                            ImportErrorRepositoryInterface::ERROR_TYPE_IMPORT,
                            $exception->getMessage(),
                            $item
                        );
                        $importErrors++;
//                    }
//                    else {
//                        \Log::error($exception->getMessage(), $exception->getTrace());
//                        $this->setError($import->_id, ' message: '. $exception->getMessage());
//                    }
                }
                $index++;
                $last++;
                if ($last == $middle) {
                    $last = 0;
                    event(new ImportProgress($import->_id, \Auth::user()->id, $index, $total));
                }
            }
            $this->importRepository->updateCounters($import->_id, [
                'imported' => $imported,
                'skipped' => $skipped,
                'importErrors' => $importErrors
            ]);
            $this->importRepository->setStatusImported($import->_id);
            event(new AfterImport($import, \Auth::user()->id));
        } catch (\Exception $exception) {
            $this->setError($import->_id, $exception->getMessage());
        }
    }

    private function countImportItems(array $actions)
    {
        $c = 0;
        foreach ($actions as $item => $value) {
            if ($value == ImportItemModel::ACTION_IMPORT) {
                $c++;
            }
        }
        return $c;
    }


}