<?php
/**
 * Created by PhpStorm.
 * User: nastia
 * Date: 20.10.17
 * Time: 13:27
 */

/**
 * Global helpers file with misc functions.
 */
if (! function_exists('configureMySqlConnectionToAccount')) {
    /**
     * Helper to create connection for account's MySql DB.
     *
     * @param int|string $accountId
     * @return string
     */
    function configureMySqlConnectionToAccount($accountId) : string
    {
        if($accountId == 'demo') {
            return $accountId;
        }
        // Just get access to the config.
        $config = App::make('config');

        // Will contain the array of connections that appear in our database config file.
        $connections = $config->get('database.connections');

        // This line pulls out the default connection by key (by default it's `mysql`)
        $defaultConnection = $connections[$config->get('database.default')];

        // Now we simply copy the default connection information to our new connection.
        $newConnection = $defaultConnection;
        // Override the database name.
        $newConnection['database'] = 'account_'.$accountId;
        $newConnection['username'] = 'account_'.$accountId;
        $newConnection['password'] = '123456';

        // This will add our new connection to the run-time configuration for the duration of the request.
        App::make('config')->set('database.connections.'.$newConnection['database'], $newConnection);

        return $newConnection['database'];
    }


}

if (! function_exists('configureMongoDBConnectionToAccount')) {
    /**
     * Helper to create connection for account's MongoDB.
     *
     * @param int|string $accountId
     * @return string
     */
    function configureMongoDBConnectionToAccount($accountId) : string
    {
        if($accountId == 'demo')
        {
            return 'mongo_demo';
        }
        if (!empty(Auth::user())) {
            if (!Auth::user()->account->is_db_created) {
                return 'mongo_demo';
            }
        }


        // Just get access to the config.
        $config = \App::make('config');

        // Will contain the array of connections that appear in our database config file.
        $connections = $config->get('database.connections');


        $defaultConnection = $connections['mongodb'];

        // Now we simply copy the default connection information to our new connection.
        $newConnection = $defaultConnection;
        // Override the database name.
        $newConnection['database'] = 'account_'.$accountId;
        $newConnection['username'] = 'account_'.$accountId;
        $newConnection['password'] = '123456';
        $newConnection['options'] = [
            'database' => 'account_'.$accountId // sets the authentication database required by mongo 3
        ];

        // This will add our new connection to the run-time configuration for the duration of the request.
        \App::make('config')->set('database.connections.mongo_'.$newConnection['database'], $newConnection);

        return 'mongo_'.$newConnection['database'];
    }


}

if (! function_exists('generateDocumentNumber')) {
    /**
     * Helper to create connection for account's MongoDB.
     *
     * @param \App\Repositories\Repository $repository
     * @return string
     */
    function generateDocumentNumber(\App\Repositories\Repository $repository) : string
    {
        $date = (string)date("dmy");
        $id = $date.'-'.(string)rand(100,999);
        if ($repository->find($id)) {
            return generateDocumentNumber($repository);
        } else {
            return $id;
        }
    }
}

if (! function_exists('formatPhoneNumber')) {

    /**
     * Format the phone number to +380231234567 or only clear it
     * @param $phoneString
     * @return string
     */
    function formatPhoneNumber($phoneString) : string
    {
        $clearNumber = preg_replace('/[^0-9]/', '',$phoneString);
        $count = strlen($clearNumber);
        switch ($count) {
            case 10 :
                $result = '+38'.$clearNumber;
                break;
            case 11:
                $result = '+3'.$clearNumber;
                break;
            case 12 :
                $result = '+'.$clearNumber;
                break;
            default :
                $result = $clearNumber;
                break;
        }
        return $result;
    }
}

if (! function_exists('checkArrayIndexes')) {

    /**
     * Checking isset many indexes by turns in array
     *
     * @param array $array
     * @param array ...$indexes
     * @return bool
     */
    function checkArrayIndexes($array, ...$indexes) : bool
    {
        if (isset($array[$indexes[0]])) {
            $nextArray = $array[$indexes[0]];
            if (is_array($nextArray) && isset($indexes[1])) {
                array_shift($indexes);
                $result = checkArrayIndexes($nextArray, ...$indexes);
                return $result;
            }
            return true;
        }
        return false;
    }
}