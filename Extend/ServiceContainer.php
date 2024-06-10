<?php

namespace Axytos\KaufAufRechnung_OXID5\Extend;

use Axytos\KaufAufRechnung_OXID5\DependencyInjection\ContainerFactory;

trait ServiceContainer
{
    /**
     * @template T
     * @psalm-param class-string<T> $serviceName
     * @return T
     * @param string $serviceName
     */
    protected function getServiceFromContainer($serviceName)
    {
        return ContainerFactory::getInstance()
            ->getContainer()
            ->get($serviceName);
    }
}
