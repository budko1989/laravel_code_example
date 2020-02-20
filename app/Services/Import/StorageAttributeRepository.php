<?php
/**
 * Created by PhpStorm.
 * User: nastia
 * Date: 26.01.18
 * Time: 14:15
 */

namespace App\Services\Import;

use App\Services\Import\Models\ImportAttributeModel;
use App\Services\Import\Models\ImportItemModel;

class StorageAttributeRepository extends StorageRepository
{

    public function pushDirty(ImportItemModel $data)
    {
        $this->storage->push('import:'.$this->importId.':attributes', $data->toJson());
    }

    public function popDirty()
    {
        $dirtyAttributes = collect($this->storage->get('import:'.$this->importId.':attributes'));

        if (!$dirtyAttributes->isEmpty()) {
            $attributes = [];
            foreach ($dirtyAttributes as $key => $attribute) {
                $this->setImportItemModel(new ImportAttributeModel());
                $attributes[$key] = $this->getItemModel()->fill(json_decode($attribute, true));
            }
            return collect($attributes);
        }
        return false;
    }

    public function deleteData()
    {
        $this->storage->del('import:'.$this->importId.':attributes');
        return true;
    }

}