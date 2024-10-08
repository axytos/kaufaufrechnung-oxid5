<?php

namespace Axytos\KaufAufRechnung_OXID5\Client;

trait OxidShopVersionAccessTrait
{
    /**
     * @return string
     */
    protected function getVersion()
    {
        $config = \oxRegistry::getConfig();

        return $config->getVersion();
    }
}
