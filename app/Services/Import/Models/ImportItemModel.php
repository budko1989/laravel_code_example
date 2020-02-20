<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 1/12/18
 * Time: 2:21 PM
 */

namespace App\Services\Import\Models;

use \Illuminate\Database\Eloquent\Model;

class ImportItemModel extends Model
{
    const ACTION_IMPORT = 'import';
    const ACTION_SKIPP = 'skipp';

}