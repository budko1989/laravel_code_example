<?php
/**
 * Created by PhpStorm.
 * User: dowell_development
 * Date: 11/29/17
 * Time: 11:58 AM
 */

namespace App\Services\Import;

use App\Services\Import\Contracts\ImportStrategyInterface;
use App\Exceptions\importException;

class ImportStrategy implements ImportStrategyInterface
{
    /**
     * @var \App\Services\Import\Contracts\ImportImplementationInterface
     */
    private $worker;

    public function __construct(
        $type,
        $context,
        $importId
    )
    {
        $className = 'App\Services\Import\Classes\\'.ucfirst($type).'\Import'.ucfirst($context);
        if (!class_exists($className)) {
            throw new importException('ClassNotFound');

        } elseif (!in_array("App\Services\Import\Contracts\ImportImplementationInterface", class_implements($className))) {
            throw new importException('ClassNotImplementInterface');
        } else {
            $this->worker = resolve($className);
            $this->worker->prepare($importId);
        }
    }

    /**
     * @return \App\Services\Import\Contracts\ImportImplementationInterface
     */
    public function getWorker()
    {
        return $this->worker;
    }

}