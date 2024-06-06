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
     * @return string|null
     */
    public function getCustomErrorMessage()
    {
        $errorMessage = $this->getSettingsValue('axytos_kaufaufrechnung_error_message');
        /** @phpstan-ignore-next-line */
        if (empty($errorMessage)) {
            return null;
        } else {
            return $errorMessage;
        }
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
