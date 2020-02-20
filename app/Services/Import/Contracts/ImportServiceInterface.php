<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 1/27/18
 * Time: 10:25 AM
 */

namespace App\Services\Import\Contracts;


interface ImportServiceInterface
{

    /**
     * @param string $type
     * @return BaseImportInterface
     */
    public function getService(string $type) : BaseImportInterface;
}