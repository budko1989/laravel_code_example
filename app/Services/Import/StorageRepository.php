<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/30/17
 * Time: 4:54 PM
 */

namespace App\Services\Import;


use App\Services\Import\Contracts\StorageInterface;
use App\Services\Import\Contracts\StorageRepositoryInterface;
use App\Services\Import\Models\ImportItemModel;

class StorageRepository implements StorageRepositoryInterface
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var string
     */
    protected $importId;

    /**
     * @var ImportItemModel
     */
    protected $importItemModel;

    /**
     * StorageRepository constructor.
     * @param StorageInterface $storage
     */
    public function __construct(
        StorageInterface $storage
    )
    {
        $this->storage = $storage;
    }

    /**
     * @return ImportItemModel
     */
    public function getItemModel()
    {
        return $this->importItemModel;
    }

    /**
     * @param $id
     * @return void
     */
    public function setImportId($id)
    {
        $this->importId = $id;
    }

    /**
     * @param ImportItemModel $model
     * @return void
     */
    public function setImportItemModel(ImportItemModel $model)
    {
        $this->importItemModel = $model;
    }

    /**

     * @param ImportItemModel $data
     * @return void
     */
    public function pushDirty(ImportItemModel $data)
    {
        $this->push($data, 'dirty_data');
    }

    /**
     * @return ImportItemModel|false
     */
    public function popDirty()
    {
        return $this->pop('dirty_data');
    }

    /**
     * @param ImportItemModel $data
     * @return void
     */
    public function pushValidated(ImportItemModel $data)
    {
        $this->push($data, 'validated_data');
    }

    /**
     * @return ImportItemModel|false
     */
    public function popValidated()
    {
        return $this->pop('validated_data');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getValidated()
    {
        return $this->get('validated_data');
    }

    /**
     * @param ImportItemModel $data
     * @return void
     */
    public function pushSkipped(ImportItemModel $data)
    {
        $this->push($data, 'skipped_data');
    }

    /**
     * @return ImportItemModel|false
     */
    public function popSkipped()
    {
        return $this->pop('skipped_data');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getSkipped()
    {
        return $this->get('skipped_data');
    }

    /**
     * @param ImportItemModel $data
     * @return void
     */
    public function pushFounded(ImportItemModel $data)
    {
        $this->push($data, 'founded_data');
    }

    /**
     * @return ImportItemModel|false
     */
    public function popFounded()
    {
        return $this->pop('founded_data');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getFounded()
    {
        return $this->get('founded_data');
    }

    /**
     * @param ImportItemModel $data
     * @param string $type
     * @return void
     */
    private function push(ImportItemModel $data, $type)
    {
        $this->storage->push('import:'.$this->importId.':'.$type, $data->toJson());
    }

    /**
     * @param string $type
     * @return ImportItemModel|false
     */
    private function pop($type)
    {
        $data = $this->storage->pop('import:'.$this->importId.':'.$type);
        if ($data) {
            return $this->getItemModel()->fill(json_decode($data, true));
        }
        return false;
    }

    /**
     * @param string $type
     * @return \Illuminate\Support\Collection
     */
    public function get($type)
    {
        return collect($this->storage->get('import:'.$this->importId.':'.$type));
    }

}