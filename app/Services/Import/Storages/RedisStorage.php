<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/30/17
 * Time: 4:55 PM
 */

namespace App\Services\Import\Storages;

use App\Services\Import\Contracts\StorageInterface;
use Illuminate\Support\Facades\Redis;

class RedisStorage implements StorageInterface
{
    public function push($key, $value)
    {
        Redis::rPush($key, $value);
    }

    public function pop($key)
    {
        return Redis::lPop($key);
    }

    public function get($key)
    {
        return Redis::lRange($key, 0, -1);
    }

    public function del($key)
    {
        return Redis::del($key);
    }

}