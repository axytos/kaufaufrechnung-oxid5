<?php

namespace Axytos\KaufAufRechnung_OXID5\Configuration;

trait OxidSettingsAccessTrait
{
    /**
     * @param string $settingName
     *
     * @return string
     */
    protected function getSettingsValue($settingName)
    {
        $settingName = (string) $settingName;
        $moduleId = 'module:axytos_kaufaufrechnung';

        /**
         * @var string
         */
        return \oxRegistry::getConfig()->getShopConfVar($settingName, null, $moduleId);
    }
}
