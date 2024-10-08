<?php

namespace Axytos\KaufAufRechnung_OXID5\Client;

use Axytos\ECommerce\Abstractions\UserAgentInfoProviderInterface;
use Axytos\ECommerce\PackageInfo\ComposerPackageInfoProvider;

class UserAgentInfoProvider implements UserAgentInfoProviderInterface
{
    use OxidShopVersionAccessTrait;

    /**
     * @var ComposerPackageInfoProvider
     */
    private $composerPackageInfoProvider;

    /**
     * @return void
     */
    public function __construct(ComposerPackageInfoProvider $composerPackageInfoProvider)
    {
        $this->composerPackageInfoProvider = $composerPackageInfoProvider;
    }

    /**
     * @return string
     */
    public function getPluginName()
    {
        return 'KaufAufRechnung';
    }

    /**
     * @return string
     */
    public function getPluginVersion()
    {
        $packageName = 'axytos/kaufaufrechnung-oxid5';

        /** @phpstan-ignore-next-line */
        return $this->composerPackageInfoProvider->getVersion($packageName);
    }

    /**
     * @return string
     */
    public function getShopSystemName()
    {
        return 'OXID-eShop';
    }

    /**
     * @return string
     */
    public function getShopSystemVersion()
    {
        return $this->getVersion();
    }
}
