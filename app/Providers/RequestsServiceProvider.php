<?php
/**
 * Created by PhpStorm.
 * User: nastia
 * Date: 31.10.17
 * Time: 14:43
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RequestsServiceProvider extends ServiceProvider
{

    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when('App\Http\Controllers\Auth\RegisterController')
            ->needs('App\Http\Requests\Contracts\RequestsInterface')
            ->give('App\Http\Requests\Auth\RegisterRequests');

        $this->app->when('App\Http\Controllers\Auth\LoginController')
            ->needs('App\Http\Requests\Contracts\RequestsInterface')
            ->give('App\Http\Requests\Auth\LoginRequests');
    }
}