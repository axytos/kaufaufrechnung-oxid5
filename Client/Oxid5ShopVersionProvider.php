<?php

namespace Axytos\KaufAufRechnung_OXID5\Client;

class Oxid5ShopVersionProvider
{
    /**
     * @return string
     */
    public function getVersion()
    {
        $config = \oxRegistry::getConfig();
        return $config->getVersion();
    }
}
