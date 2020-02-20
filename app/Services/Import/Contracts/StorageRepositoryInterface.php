<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/30/17
 * Time: 4:53 PM
 */

namespace App\Services\Import\Contracts;

use App\Services\Import\Models\ImportItemModel;

interface StorageRepositoryInterface
{
    /**
     * @param $id
     * @return mixed
     */
    public function setImportId($id);

    /**
     * @param ImportItemModel $model
     * @return void
     */
    public function setImportItemModel(ImportItemModel $model);

    /**
     * @param ImportItemModel $data
     * @return mixed
     */
    public function pushDirty(ImportItemModel $data);

    /**
     * @return mixed
     */
    public function popDirty();

    /**
     * @param ImportItemModel $data
     * @return mixed
     */
    public function pushValidated(ImportItemModel $data);

    /**
     * @return mixed
     */
    public function popValidated();

    public function getValidated();

    public function pushSkipped(ImportItemModel $data);

    public function popSkipped();

    public function getSkipped();

    public function pushFounded(ImportItemModel $data);

    public function popFounded();

    public function getFounded();

}