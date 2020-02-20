<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 1/16/18
 * Time: 5:35 PM
 */

namespace App\Providers;


use Illuminate\Support\ServiceProvider;

class IntergrationServiceProvider extends ServiceProvider
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

        $this->app->bind('App\Services\Integration\Contracts\IntegrationServiceInterface',
            'App\Services\Integration\IntegrationService');

        //Open Cart
        $this->app->when('App\Services\Integration\OpenCartIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationProductInterface')
            ->give('App\Services\Integration\Product\OpenCartProductIntegration');

        $this->app->when('App\Services\Integration\OpenCartIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationOrderInterface')
            ->give('App\Services\Integration\Order\OpenCartOrderIntegration');

        // Magento
        $this->app->when('App\Services\Integration\MagentoIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationProductInterface')
            ->give('App\Services\Integration\Product\MagentoProductIntegration');

        $this->app->when('App\Services\Integration\MagentoIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationAttributeInterface')
            ->give('App\Services\Integration\Attribute\MagentoAttributeIntegration');

        $this->app->when('App\Services\Integration\MagentoIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationOrderInterface')
            ->give('App\Services\Integration\Order\MagentoOrderIntegration');

        //Prom UA
        $this->app->when('App\Services\Integration\PromuaIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationProductInterface')
            ->give('App\Services\Integration\Product\PromuaProductIntegration');

        $this->app->when('App\Services\Integration\PromuaIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationOrderInterface')
            ->give('App\Services\Integration\Order\PromuaOrderIntegration');

        //WordpressIntegration
        $this->app->when('App\Services\Integration\WordpressIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationProductInterface')
            ->give('App\Services\Integration\Product\WordpressProductIntegration');

        $this->app->when('App\Services\Integration\WordpressIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationOrderInterface')
            ->give('App\Services\Integration\Order\WordpressOrderIntegration');

        //RozetkaIntegration
        $this->app->when('App\Services\Integration\RozetkaIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationProductInterface')
            ->give('App\Services\Integration\Product\WordpressProductIntegration');

        $this->app->when('App\Services\Integration\RozetkaIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationOrderInterface')
            ->give('App\Services\Integration\Order\RozetkaOrderIntegration');

        // Prestashop
        $this->app->when('App\Services\Integration\PrestashopIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationProductInterface')
            ->give('App\Services\Integration\Product\PrestashopProductIntegration');

        $this->app->when('App\Services\Integration\PrestashopIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationOrderInterface')
            ->give('App\Services\Integration\Order\PrestashopOrderIntegration');

        //Empty
        $this->app->when('App\Services\Integration\EmptyIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationProductInterface')
            ->give('App\Services\Integration\Product\EmptyProductIntegration');

        $this->app->when('App\Services\Integration\EmptyIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationOrderInterface')
            ->give('App\Services\Integration\Order\EmptyOrderIntegration');

        //M2M
        $this->app->when('App\Services\Integration\M2MIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationProductInterface')
            ->give('App\Services\Integration\Product\M2MProductIntegration');

        $this->app->when('App\Services\Integration\M2MIntegration')
            ->needs('App\Services\Integration\Contracts\IntegrationOrderInterface')
            ->give('App\Services\Integration\Order\M2MOrderIntegration');
    }
}