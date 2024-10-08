<?php

namespace Axytos\KaufAufRechnung_OXID5\Configuration;

class PluginConfiguration
{
    use OxidSettingsAccessTrait;

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
        return $this->getSettingsValue('axytos_kaufaufrechnung_client_secret');
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
        }

        return $errorMessage;
    }
}
