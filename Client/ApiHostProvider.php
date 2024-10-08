<?php

namespace Axytos\KaufAufRechnung_OXID5\Client;

use Axytos\ECommerce\Abstractions\ApiHostProviderInterface;
use Axytos\KaufAufRechnung_OXID5\Configuration\PluginConfiguration;

class ApiHostProvider implements ApiHostProviderInterface
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
     *
     * @phpstan-return self::LIVE|self::SANDBOX
     */
    public function getApiHost()
    {
        $option = $this->pluginConfig->getApiHost();
        switch ($option) {
            case 'APIHOST_LIVE':
                return self::LIVE;
            case 'APIHOST_SANDBOX':
                return self::SANDBOX;
            default:
                return self::SANDBOX;
        }
    }
}
