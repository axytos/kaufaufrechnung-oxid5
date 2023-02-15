<?php

namespace Axytos\KaufAufRechnung_OXID5\Configuration;

use oxRegistry;

class PluginConfiguration
{
    /**
     * @return string
     */
    public function getApiHost()
    {
        return $this->getSettingsValue('axytos_kaufaufrechnung_api_host');
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->getSettingsValue('axytos_kaufaufrechnung_api_key');
    }

    /**
     * @return string|null
     */
    public function getClientSecret()
    {
        return $this->getSettingsValue('axytos_kaufaufrechnung_api_client_secret');
    }

    /**
     * @return string
     * @param string $settingName
     */
    private function getSettingsValue($settingName)
    {
        $settingName = (string) $settingName;
        $moduleId = 'module:axytos_kaufaufrechnung';

        /**
         * @var string
         */
        $value = oxRegistry::getConfig()->getShopConfVar($settingName, null, $moduleId);
        return $value;
    }
}
