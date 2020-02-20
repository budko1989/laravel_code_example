<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/27/17
 * Time: 12:15 PM
 */

namespace App\Services\Import\Contracts;

use Illuminate\Database\Eloquent\Model;

interface BaseImportInterface
{

    public function load(Model $import);

    public function validate(Model $import);

    public function getSkipped(Model $import);

    public function getFounded(Model $import);

    public function getValidated(Model $import);

    public function import(Model $import);

}