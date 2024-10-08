<?php

namespace Axytos\KaufAufRechnung_OXID5\Client;

use Axytos\ECommerce\Abstractions\ApiKeyProviderInterface;
use Axytos\KaufAufRechnung_OXID5\Configuration\PluginConfiguration;

class ApiKeyProvider implements ApiKeyProviderInterface
{
    /**
     * @var PluginConfiguration
     */
    public $pluginConfig;

    public function __construct(PluginConfiguration $pluginConfig)
    {
        $this->pluginConfig = $pluginConfig;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->pluginConfig->getApiKey();
    }
}
