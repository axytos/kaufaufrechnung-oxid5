<?php

namespace Axytos\KaufAufRechnung_OXID5\Extend;

use Axytos\KaufAufRechnung_OXID5\DependencyInjection\ContainerFactory;

trait AxytosServiceContainer
{
    /**
     * @template T
     * @psalm-param class-string<T> $serviceName
     * @return T
     * @param string $serviceName
     */
    protected function getFromAxytosServiceContainer($serviceName)
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get($serviceName);
    }
}
