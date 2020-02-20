<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/30/17
 * Time: 4:55 PM
 */

namespace App\Services\Import\Contracts;


interface StorageInterface
{
    public function push($key, $value);

    public function pop($key);

    public function get($key);

    public function del($key);
}