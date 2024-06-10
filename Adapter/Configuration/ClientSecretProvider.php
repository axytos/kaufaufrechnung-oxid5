<?php

namespace Axytos\KaufAufRechnung_OXID5\Adapter\Configuration;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Configuration\ClientSecretProviderInterface;
use Axytos\KaufAufRechnung_OXID5\Configuration\PluginConfiguration;

class ClientSecretProvider implements ClientSecretProviderInterface
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\Configuration\PluginConfiguration
     */
    private $pluginConfiguration;

    public function __construct(PluginConfiguration $pluginConfiguration)
    {
        $this->pluginConfiguration = $pluginConfiguration;
    }

    /**
     * @return string|null
     */
    public function getClientSecret()
    {
        return $this->pluginConfiguration->getClientSecret();
    }
}
