<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/29/17
 * Time: 11:58 AM
 */

namespace App\Services\Import\Contracts;


interface ImportStrategyInterface
{
    /**
     * @return ImportImplementationInterface
     */
    public function getWorker();
}