<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Broadcast::routes(['middleware' => ['api', 'auth:api']]);

        /*
         * Authenticate the user's personal channel...
         */
        Broadcast::channel('user.{userId}', function ($user, $userId) {
            return (int) $user->id === (int) $userId;
        });


        Broadcast::channel('import.{accountId}', function ($import, $accountId) {
            return (int) $import->account_id === (int)$accountId;
        });

        Broadcast::channel('orders.{accountId}', function ($user, $accountId) {
            /**
             * @var $user \App\Models\User
             */
            return (int) $user->account_id === (int) $accountId;
        });

        Broadcast::channel('payment.{accountId}', function ($refillModel, $accountId) {
            return (int) $refillModel->account_id === (int)$accountId;
        });
    }
}
