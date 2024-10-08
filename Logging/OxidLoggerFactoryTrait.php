<?php

namespace Axytos\KaufAufRechnung_OXID5\Logging;

trait OxidLoggerFactoryTrait
{
    /**
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger()
    {
        /** @var \Psr\Log\LoggerInterface */
        return new Oxid5PsrLikeLogger();
    }
}
