<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 1/27/18
 * Time: 10:28 AM
 */

namespace App\Services\Import;


use App\Exceptions\importException;
use App\Repositories\PerAccountsRepositories\Contracts\ImportRepositoryInterface;
use App\Services\Import\Contracts\BaseImportInterface;
use App\Services\Import\Contracts\ImportServiceInterface;

class ImportService implements ImportServiceInterface
{

    /**
     * @param string $type
     * @return BaseImportInterface
     * @throws importException
     */
    public function getService(string $type): BaseImportInterface
    {
        switch ($type) {
            case ImportRepositoryInterface::TYPE_PRODUCT:
                return resolve(ImportProductService::class);
                break;

            case ImportRepositoryInterface::TYPE_CATEGORY:
                //TODO create import category service
                return null;
                break;

            case ImportRepositoryInterface::TYPE_ORDER:
                return resolve(ImportOrderService::class);
                break;

            case ImportRepositoryInterface::TYPE_ATTRIBUTE:
                return resolve(ImportAttributeService::class);
                break;

            default:
                throw new importException("UnknownImportType");
                break;
        }
    }

}