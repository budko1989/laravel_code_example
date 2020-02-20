<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 1/16/18
 * Time: 5:35 PM
 */

namespace App\Providers;


use Illuminate\Support\ServiceProvider;

class NovaposhtaServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        // services
        $this->app->bind('App\Services\Novaposhta\Contracts\NovaposhtaServiceInterface',
            'App\Services\Novaposhta\NovaposhtaService');


        $this->app->bind('App\Services\Novaposhta\Address\Contracts\NovaposhtaAddressServiceInterface',
            'App\Services\Novaposhta\Address\NovaposhtaAddressService');
        $this->app->bind('App\Services\Novaposhta\Counterparty\Contracts\NovaposhtaCounterpartyServiceInterface',
            'App\Services\Novaposhta\Counterparty\NovaposhtaCounterpartyService');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\NovaposhtaCommonServiceInterface',
            'App\Services\Novaposhta\Common\NovaposhtaCommonService');
        $this->app->bind('App\Services\Novaposhta\AdditionalService\Contracts\NovaposhtaAdditionalServiceInterface',
                    'App\Services\Novaposhta\AdditionalService\NovaposhtaAdditionalService');

        //counterparty
        $this->app->bind('App\Services\Novaposhta\Counterparty\Contracts\NovaposhtaCounterpartySenderServiceInterface',
            'App\Services\Novaposhta\Counterparty\NovaposhtaCounterpartySenderService');
        $this->app->bind('App\Services\Novaposhta\Counterparty\Contracts\NovaposhtaCounterpartyRecipientServiceInterface',
            'App\Services\Novaposhta\Counterparty\NovaposhtaCounterpartyRecipientService');
        $this->app->bind('App\Services\Novaposhta\Counterparty\Contracts\NovaposhtaCounterpartyContactPersonServiceInterface',
            'App\Services\Novaposhta\Counterparty\NovaposhtaCounterpartyContactPersonService');
        $this->app->bind('App\Services\Novaposhta\Counterparty\Contracts\NovaposhtaCounterpartyAddressServiceInterface',
            'App\Services\Novaposhta\Counterparty\NovaposhtaCounterpartyAddressService');

        // address
        $this->app->bind('App\Services\Novaposhta\Address\Contracts\ReferenceAreaInterface',
            'App\Services\Novaposhta\Address\References\Area');
        $this->app->bind('App\Services\Novaposhta\Address\Contracts\ReferenceSettlementInterface',
            'App\Services\Novaposhta\Address\References\Settlement');
        $this->app->bind('App\Services\Novaposhta\Address\Contracts\ReferenceCityInterface',
            'App\Services\Novaposhta\Address\References\City');
        $this->app->bind('App\Services\Novaposhta\Address\Contracts\ReferenceWarehouseInterface',
            'App\Services\Novaposhta\Address\References\Warehouse');
        $this->app->bind('App\Services\Novaposhta\Address\Contracts\ReferenceWarehouseTypeInterface',
            'App\Services\Novaposhta\Address\References\WarehouseType');
        $this->app->bind('App\Services\Novaposhta\Address\Contracts\ReferenceStreetInterface',
            'App\Services\Novaposhta\Address\References\Street');

        // common
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceTimeIntervalInterface',
            'App\Services\Novaposhta\Common\References\TimeInterval');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceCargoTypeInterface',
            'App\Services\Novaposhta\Common\References\CargoType');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceBackwardDeliveryCargoTypeInterface',
            'App\Services\Novaposhta\Common\References\BackwardDeliveryCargoType');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceOwnershipFormInterface',
            'App\Services\Novaposhta\Common\References\OwnershipForm');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferencePaymentFormInterface',
            'App\Services\Novaposhta\Common\References\PaymentForm');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceCounterpartyTypeInterface',
            'App\Services\Novaposhta\Common\References\CounterpartyType');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceCounterpartyTypeInterface',
            'App\Services\Novaposhta\Common\References\CounterpartyType');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceServiceTypeInterface',
            'App\Services\Novaposhta\Common\References\ServiceType');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceCargoDescriptionInterface',
            'App\Services\Novaposhta\Common\References\CargoDescription');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferencePalletsListInterface',
            'App\Services\Novaposhta\Common\References\PalletsList');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceTypesOfPayerInterface',
            'App\Services\Novaposhta\Common\References\TypesOfPayer');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceTypesOfPayersForRedeliveryInterface',
            'App\Services\Novaposhta\Common\References\TypesOfPayersForRedelivery');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferencePackListInterface',
            'App\Services\Novaposhta\Common\References\PackList');
        $this->app->bind('App\Services\Novaposhta\Common\Contracts\ReferenceTiresWheelInterface',
            'App\Services\Novaposhta\Common\References\TiresWheel');

        //internetDocument
        $this->app->bind('App\Services\Novaposhta\InternetDocument\Contracts\NovaposhtaInternetDocumentServiceInterface',
            'App\Services\Novaposhta\InternetDocument\NovaposhtaInternetDocumentService');
        // additional service
        $this->app->bind('App\Services\Novaposhta\AdditionalService\Contracts\ReferenceReturnReasonInterface',
            'App\Services\Novaposhta\AdditionalService\References\ReturnReason');
        $this->app->bind('App\Services\Novaposhta\AdditionalService\Contracts\ReferenceReturnReasonsSubtypeInterface',
                    'App\Services\Novaposhta\AdditionalService\References\ReturnReasonsSubtype');
        //document preset
        $this->app->bind('App\Services\Novaposhta\InternetDocument\Contracts\InternetDocumentPresetServiceInterface',
            'App\Services\Novaposhta\InternetDocument\InternetDocumentPresetService');
        $this->app->bind('App\Services\Novaposhta\ScanSheet\Contracts\ScanSheetServiceInterface',
            'App\Services\Novaposhta\ScanSheet\ScanSheetService');


        // repositories
        $this->app->bind('App\Repositories\Novaposhta\Address\Contracts\NovaposhtaAreaRepositoryInterface',
            'App\Repositories\Novaposhta\Address\NovaposhtaAreaRepository');
        $this->app->bind('App\Repositories\Novaposhta\Address\Contracts\NovaposhtaCityRepositoryInterface',
            'App\Repositories\Novaposhta\Address\NovaposhtaCityRepository');
        $this->app->bind('App\Repositories\Novaposhta\Address\Contracts\NovaposhtaWarehouseRepositoryInterface',
            'App\Repositories\Novaposhta\Address\NovaposhtaWarehouseRepository');
        $this->app->bind('App\Repositories\Novaposhta\Address\Contracts\NovaposhtaWarehouseTypeRepositoryInterface',
            'App\Repositories\Novaposhta\Address\NovaposhtaWarehouseTypeRepository');
        $this->app->bind('App\Repositories\Novaposhta\Address\Contracts\NovaposhtaSettlementRepositoryInterface',
            'App\Repositories\Novaposhta\Address\NovaposhtaSettlementRepository');
        $this->app->bind('App\Repositories\Novaposhta\Address\Contracts\NovaposhtaStreetRepositoryInterface',
            'App\Repositories\Novaposhta\Address\NovaposhtaStreetRepository');

        $this->app->bind('App\Repositories\Novaposhta\Counterparty\Contracts\NovaposhtaCounterpartyRepositoryInterface',
            'App\Repositories\Novaposhta\Counterparty\NovaposhtaCounterpartyRepository');
        $this->app->bind('App\Repositories\Novaposhta\Counterparty\Contracts\NovaposhtaContactPersonRepositoryInterface',
            'App\Repositories\Novaposhta\Counterparty\NovaposhtaContactPersonRepository');
        $this->app->bind('App\Repositories\Novaposhta\Counterparty\Contracts\NovaposhtaAddressRepositoryInterface',
            'App\Repositories\Novaposhta\Counterparty\NovaposhtaAddressRepository');

        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaTimeIntervalRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaTimeIntervalRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaCargoTypeRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaCargoTypeRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaBackwardDeliveryCargoTypeRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaBackwardDeliveryCargoTypeRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaOwnershipFormRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaOwnershipFormRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaPaymentFormRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaPaymentFormRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaCounterpartyTypeRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaCounterpartyTypeRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaServiceTypeRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaServiceTypeRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaCargoDescriptionRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaCargoDescriptionRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaPalletsListRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaPalletsListRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaTypesOfPayerRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaTypesOfPayerRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaTypesOfPayersForRedeliveryRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaTypesOfPayersForRedeliveryRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaPackListRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaPackListRepository');
        $this->app->bind('App\Repositories\Novaposhta\Common\Contracts\NovaposhtaTiresWheelRepositoryInterface',
            'App\Repositories\Novaposhta\Common\NovaposhtaTiresWheelRepository');

        $this->app->bind('App\Repositories\Novaposhta\AdditionalService\Contracts\NovaposhtaReturnReasonRepositoryInterface',
            'App\Repositories\Novaposhta\AdditionalService\NovaposhtaReturnReasonRepository');
        $this->app->bind('App\Repositories\Novaposhta\AdditionalService\Contracts\NovaposhtaReturnReasonsSubtypeRepositoryInterface',
                    'App\Repositories\Novaposhta\AdditionalService\NovaposhtaReturnReasonsSubtypeRepository');

        $this->app->bind('App\Repositories\Novaposhta\InternetDocument\Contracts\NovaposhtaInternetDocumentRepositoryInterface',
                    'App\Repositories\Novaposhta\InternetDocument\NovaposhtaInternetDocumentRepository');
        $this->app->bind('App\Repositories\Novaposhta\ScanSheet\Contracts\ScanSheetResponseRepositoryInterface',
            'App\Repositories\Novaposhta\ScanSheet\ScanSheetResponseResponseRepository');
        $this->app->bind('App\Repositories\Novaposhta\ScanSheet\Contracts\ScanSheetRepositoryInterface',
            'App\Repositories\Novaposhta\ScanSheet\ScanSheetRepository');

        $this->app->bind('App\Repositories\Novaposhta\InternetDocument\Contracts\InternetDocumentPresetRepositoryInterface',
        'App\Repositories\Novaposhta\InternetDocument\InternetDocumentPresetRepository');



    }
}