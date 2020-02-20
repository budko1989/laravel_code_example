<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/27/17
 * Time: 12:15 PM
 */

namespace App\Services\Import\Contracts;


interface ImportImplementationInterface
{
    /**
     * @param $importId
     * @return mixed
     */
    public function prepare($importId);

}