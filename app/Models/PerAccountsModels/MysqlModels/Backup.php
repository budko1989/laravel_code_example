<?php

namespace App\Models\PerAccountsModels\MysqlModels;

/**
 * @property integer $id
 * @property int $user_id
 * @property string $backup_name
 * @property string $created_at
 * @property string $updated_at
 */
class Backup extends BasePerAccountModel
{
    /**
     * The "type" of the auto-incrementing ID.
     * 
     * @var string
     */
    protected $keyType = 'integer';

    /**
     * @var array
     */
    protected $fillable = ['user_id', 'backup_name', 'created_at', 'updated_at'];


}
