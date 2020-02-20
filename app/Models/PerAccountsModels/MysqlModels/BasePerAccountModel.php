<?php
/**
 * Created by PhpStorm.
 * User: nastia
 * Date: 20.10.17
 * Time: 13:39
 */

namespace App\Models\PerAccountsModels\MysqlModels;

use Illuminate\Database\Eloquent\Model;
use Auth;

class BasePerAccountModel extends Model
{
    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);

        // Set the database connection name.
        if (!empty(Auth::user())) {
            if (Auth::user()->account->is_db_created) {
                $this->setConnection(configureMySqlConnectionToAccount(Auth::user()->account_id));
            }
        }
        else {
            $this->setConnection('demo');
        }
    }
}