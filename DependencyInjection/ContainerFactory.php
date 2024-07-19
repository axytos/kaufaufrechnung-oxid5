<?php

namespace Axytos\KaufAufRechnung_OXID5\DependencyInjection;

use Axytos\ECommerce\Abstractions\ApiHostProviderInterface;
use Axytos\ECommerce\Abstractions\ApiKeyProviderInterface;
use Axytos\ECommerce\Abstractions\FallbackModeConfigurationInterface;
use Axytos\ECommerce\Abstractions\PaymentMethodConfigurationInterface;
use Axytos\ECommerce\Abstractions\UserAgentInfoProviderInterface;
use Axytos\ECommerce\AxytosECommerceClient;
use Axytos\ECommerce\Clients\Checkout\CheckoutClientInterface;
use Axytos\ECommerce\Clients\ErrorReporting\ErrorReportingClient;
use Axytos\ECommerce\Clients\ErrorReporting\ErrorReportingClientInterface;
use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\ECommerce\DataMapping\DtoArrayMapper;
use Axytos\ECommerce\DependencyInjection\Container;
use Axytos\ECommerce\DependencyInjection\ContainerBuilder;
use Axytos\ECommerce\Logging\LoggerAdapterInterface;
use Axytos\ECommerce\PackageInfo\ComposerPackageInfoProvider;
use Axytos\ECommerce\Tests\Integration\ErrorReportingClientIntegrationTest;
use Axytos\KaufAufRechnung\Core\Abstractions\Model\Actions\ActionExecutorInterface;
use Axytos\KaufAufRechnung\Core\Model\Actions\ActionExecutor;
use Axytos\KaufAufRechnung\Core\Model\AxytosOrderCommandFacade;
use Axytos\KaufAufRechnung\Core\Model\AxytosOrderFactory;
use Axytos\KaufAufRechnung\Core\OrderSyncWorker;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Configuration\ClientSecretProviderInterface;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Database\DatabaseTransactionFactoryInterface;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface as KARCoreLoggerAdapterInterface;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\OrderSyncRepositoryInterface;
use Axytos\KaufAufRechnung_OXID5\Adapter\Configuration\ClientSecretProvider;
use Axytos\KaufAufRechnung_OXID5\Adapter\Database\DatabaseTransaction;
use Axytos\KaufAufRechnung_OXID5\Adapter\Database\DatabaseTransactionFactory;
use Axytos\KaufAufRechnung_OXID5\Adapter\HashCalculation\HashAlgorithmInterface;
use Axytos\KaufAufRechnung_OXID5\Adapter\HashCalculation\HashCalculator;
use Axytos\KaufAufRechnung_OXID5\Adapter\HashCalculation\SHA256HashAlgorithm;
use Axytos\KaufAufRechnung_OXID5\Adapter\Logging\LoggerAdapter as KARCoreLoggerAdapter;
use Axytos\KaufAufRechnung_OXID5\Adapter\OrderSyncRepository;
use Axytos\KaufAufRechnung_OXID5\Adapter\PluginOrderFactory;
use Axytos\KaufAufRechnung_OXID5\Client\ApiHostProvider;
use Axytos\KaufAufRechnung_OXID5\Client\ApiKeyProvider;
use Axytos\KaufAufRechnung_OXID5\Client\FallbackModeConfiguration;
use Axytos\KaufAufRechnung_OXID5\Client\Oxid5ShopVersionProvider;
use Axytos\KaufAufRechnung_OXID5\Client\PaymentMethodConfiguration;
use Axytos\KaufAufRechnung_OXID5\Client\UserAgentInfoProvider;
use Axytos\KaufAufRechnung_OXID5\Configuration\PluginConfiguration;
use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContextFactory;
use Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketPositionDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketPositionDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceTaxGroupDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceTaxGroupDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CustomerDataDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\DeliveryAddressDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\InvoiceAddressDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\ShippingBasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\ShippingBasketPositionDtoFactory;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung_OXID5\Logging\LoggerAdapter;
use Axytos\KaufAufRechnung_OXID5\OrderSync\OrderSyncCronJob;
use Axytos\KaufAufRechnung_OXID5\OrderSync\ShopSystemOrderFactory;
use Axytos\KaufAufRechnung_OXID5\OrderSync\ShopSystemOrderRepository;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\DeliveryWeightCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\LogisticianCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\TrackingIdCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator;

class ContainerFactory
{
    /**
     * @var self|null
     */
    private static $instance = null;

    /**
     * @var Container
     */
    private $container = null;

    private function __construct()
    {
    }

    /**
     * @return \Axytos\ECommerce\DependencyInjection\Container
     */
    public function getContainer()
    {
        if ($this->container === null) {
            $this->initializeContainer();
        }

        return $this->container;
    }

    /**
     * @return void
     */
    private function initializeContainer()
    {
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->registerFactory(PluginConfiguration::class, function () {
            return new PluginConfiguration();
        });
        $containerBuilder->registerFactory(ApiHostProvider::class, function ($container) {
            return new ApiHostProvider($container->get(PluginConfiguration::class));
        });
        $containerBuilder->registerFactory(ApiKeyProvider::class, function ($container) {
            return new ApiKeyProvider($container->get(PluginConfiguration::class));
        });
        $containerBuilder->registerFactory(PaymentMethodConfiguration::class, function () {
            return new PaymentMethodConfiguration();
        });
        $containerBuilder->registerFactory(Oxid5ShopVersionProvider::class, function () {
            return new Oxid5ShopVersionProvider();
        });
        $containerBuilder->registerFactory(LoggerAdapter::class, function () {
            return new LoggerAdapter();
        });
        $containerBuilder->registerFactory(ErrorHandler::class, function ($container) {
            return new ErrorHandler($container->get(ErrorReportingClientInterface::class));
        });
        $containerBuilder->registerFactory(InvoiceOrderContextFactory::class, function ($container) {
            return new InvoiceOrderContextFactory(
                $container->get(CustomerDataDtoFactory::class),
                $container->get(InvoiceAddressDtoFactory::class),
                $container->get(DeliveryAddressDtoFactory::class),
                $container->get(BasketDtoFactory::class),
                $container->get(CreateInvoiceBasketDtoFactory::class),
                $container->get(ShippingBasketPositionDtoCollectionFactory::class),
                $container->get(TrackingIdCalculator::class),
                $container->get(LogisticianCalculator::class)
            );
        });
        $containerBuilder->registerFactory(ShippingBasketPositionDtoCollectionFactory::class, function ($container) {
            return new ShippingBasketPositionDtoCollectionFactory(
                $container->get(ShippingBasketPositionDtoFactory::class)
            );
        });
        $containerBuilder->registerFactory(ShippingBasketPositionDtoFactory::class, function () {
            return new ShippingBasketPositionDtoFactory();
        });
        $containerBuilder->registerFactory(CreateInvoiceBasketDtoFactory::class, function ($container) {
            return new CreateInvoiceBasketDtoFactory(
                $container->get(CreateInvoiceBasketPositionDtoCollectionFactory::class),
                $container->get(CreateInvoiceTaxGroupDtoCollectionFactory::class),
                $container->get(ShippingCostCalculator::class)
            );
        });
        $containerBuilder->registerFactory(CreateInvoiceBasketPositionDtoCollectionFactory::class, function ($container) {
            return new CreateInvoiceBasketPositionDtoCollectionFactory(
                $container->get(CreateInvoiceBasketPositionDtoFactory::class)
            );
        });
        $containerBuilder->registerFactory(CreateInvoiceBasketPositionDtoFactory::class, function ($container) {
            return new CreateInvoiceBasketPositionDtoFactory(
                $container->get(ShippingCostCalculator::class),
                $container->get(VoucherDiscountCalculator::class)
            );
        });
        $containerBuilder->registerFactory(CreateInvoiceTaxGroupDtoCollectionFactory::class, function ($container) {
            return new CreateInvoiceTaxGroupDtoCollectionFactory(
                $container->get(CreateInvoiceTaxGroupDtoFactory::class)
            );
        });
        $containerBuilder->registerFactory(CreateInvoiceTaxGroupDtoFactory::class, function ($container) {
            return new CreateInvoiceTaxGroupDtoFactory(
                $container->get(ShippingCostCalculator::class),
                $container->get(VoucherDiscountCalculator::class)
            );
        });
        $containerBuilder->registerFactory(CustomerDataDtoFactory::class, function () {
            return new CustomerDataDtoFactory();
        });
        $containerBuilder->registerFactory(InvoiceAddressDtoFactory::class, function ($container) {
            return new InvoiceAddressDtoFactory(
                $container->get(OrderRepository::class)
            );
        });
        $containerBuilder->registerFactory(DeliveryAddressDtoFactory::class, function ($container) {
            return new DeliveryAddressDtoFactory(
                $container->get(OrderRepository::class)
            );
        });
        $containerBuilder->registerFactory(BasketDtoFactory::class, function ($container) {
            return new BasketDtoFactory(
                $container->get(BasketPositionDtoCollectionFactory::class),
                $container->get(ShippingCostCalculator::class)
            );
        });
        $containerBuilder->registerFactory(BasketPositionDtoCollectionFactory::class, function ($container) {
            return new BasketPositionDtoCollectionFactory(
                $container->get(BasketPositionDtoFactory::class)
            );
        });
        $containerBuilder->registerFactory(BasketPositionDtoFactory::class, function ($container) {
            return new BasketPositionDtoFactory(
                $container->get(ShippingCostCalculator::class),
                $container->get(VoucherDiscountCalculator::class)
            );
        });
        $containerBuilder->registerFactory(ApiHostProviderInterface::class, function ($container) {
            return $container->get(ApiHostProvider::class);
        });
        $containerBuilder->registerFactory(ApiKeyProviderInterface::class, function ($container) {
            return $container->get(ApiKeyProvider::class);
        });
        $containerBuilder->registerFactory(UserAgentInfoProviderInterface::class, function ($container) {
            return new UserAgentInfoProvider(
                $container->get(ComposerPackageInfoProvider::class),
                $container->get(Oxid5ShopVersionProvider::class)
            );
        });
        $containerBuilder->registerFactory(FallbackModeConfigurationInterface::class, function () {
            return new FallbackModeConfiguration();
        });
        $containerBuilder->registerFactory(PaymentMethodConfigurationInterface::class, function ($container) {
            return $container->get(PaymentMethodConfiguration::class);
        });
        $containerBuilder->registerFactory(PluginConfigurationValidator::class, function ($container) {
            return new PluginConfigurationValidator(
                $container->get(ApiHostProviderInterface::class),
                $container->get(ApiKeyProviderInterface::class)
            );
        });
        $containerBuilder->registerFactory(ComposerPackageInfoProvider::class, function () {
            return new ComposerPackageInfoProvider();
        });
        $containerBuilder->registerFactory(LoggerAdapterInterface::class, function ($container) {
            return $container->get(LoggerAdapter::class);
        });
        $containerBuilder->registerFactory(AxytosECommerceClient::class, function ($container) {
            return new AxytosECommerceClient(
                $container->get(ApiHostProviderInterface::class),
                $container->get(ApiKeyProviderInterface::class),
                $container->get(PaymentMethodConfigurationInterface::class),
                $container->get(FallbackModeConfigurationInterface::class),
                $container->get(UserAgentInfoProviderInterface::class),
                $container->get(LoggerAdapterInterface::class)
            );
        });
        $containerBuilder->registerFactory(InvoiceClientInterface::class, function ($container) {
            return $container->get(AxytosECommerceClient::class);
        });
        $containerBuilder->registerFactory(CheckoutClientInterface::class, function ($container) {
            return $container->get(AxytosECommerceClient::class);
        });
        $containerBuilder->registerFactory(ErrorReportingClientInterface::class, function ($container) {
            return $container->get(AxytosECommerceClient::class);
        });
        $containerBuilder->registerFactory(DeliveryWeightCalculator::class, function () {
            return new DeliveryWeightCalculator();
        });
        $containerBuilder->registerFactory(LogisticianCalculator::class, function ($container) {
            return new LogisticianCalculator($container->get(OrderRepository::class));
        });
        $containerBuilder->registerFactory(TrackingIdCalculator::class, function () {
            return new TrackingIdCalculator();
        });
        $containerBuilder->registerFactory(ShippingCostCalculator::class, function () {
            return new ShippingCostCalculator();
        });
        $containerBuilder->registerFactory(VoucherDiscountCalculator::class, function () {
            return new VoucherDiscountCalculator();
        });
        $containerBuilder->registerFactory(OrderRepository::class, function ($container) {
            return new OrderRepository();
        });
        $containerBuilder->registerFactory(OrderSyncWorker::class, function ($container) {
            return new OrderSyncWorker(
                $container->get(OrderSyncRepositoryInterface::class),
                $container->get(AxytosOrderFactory::class),
                $container->get(KARCoreLoggerAdapterInterface::class),
                $container->get(ErrorReportingClientInterface::class)
            );
        });
        $containerBuilder->registerFactory(OrderSyncCronJob::class, function ($container) {
            return new OrderSyncCronJob(
                $container->get(PluginConfigurationValidator::class),
                $container->get(OrderSyncWorker::class),
                $container->get(LoggerAdapterInterface::class),
                $container->get(ErrorHandler::class)
            );
        });
        $containerBuilder->registerFactory(DtoArrayMapper::class, function ($container) {
            return new DtoArrayMapper();
        });
        $containerBuilder->registerFactory(OrderSyncRepositoryInterface::class, function ($container) {
            return new OrderSyncRepository(
                $container->get(OrderRepository::class),
                $container->get(PluginOrderFactory::class)
            );
        });
        $containerBuilder->registerFactory(PluginOrderFactory::class, function ($container) {
            return new PluginOrderFactory(
                $container->get(InvoiceOrderContextFactory::class),
                $container->get(HashCalculator::class)
            );
        });
        $containerBuilder->registerFactory(HashAlgorithmInterface::class, function ($container) {
            return new SHA256HashAlgorithm();
        });
        $containerBuilder->registerFactory(HashCalculator::class, function ($container) {
            return new HashCalculator(
                $container->get(HashAlgorithmInterface::class)
            );
        });
        $containerBuilder->registerFactory(AxytosOrderFactory::class, function ($container) {
            return new AxytosOrderFactory(
                $container->get(ErrorReportingClientInterface::class),
                $container->get(DatabaseTransactionFactoryInterface::class),
                $container->get(AxytosOrderCommandFacade::class),
                $container->get(KARCoreLoggerAdapterInterface::class)
            );
        });
        $containerBuilder->registerFactory(DatabaseTransactionFactoryInterface::class, function ($container) {
            return new DatabaseTransactionFactory(
                $container->get(OrderRepository::class)
            );
        });
        $containerBuilder->registerFactory(AxytosOrderCommandFacade::class, function ($container) {
            return new AxytosOrderCommandFacade(
                $container->get(InvoiceClientInterface::class),
                $container->get(ErrorReportingClientInterface::class),
                $container->get(KARCoreLoggerAdapterInterface::class)
            );
        });
        $containerBuilder->registerFactory(KARCoreLoggerAdapterInterface::class, function ($container) {
            return new KARCoreLoggerAdapter(
                $container->get(LoggerAdapterInterface::class)
            );
        });
        $containerBuilder->registerFactory(ActionExecutorInterface::class, function ($container) {
            return new ActionExecutor(
                $container->get(ClientSecretProviderInterface::class),
                $container->get(OrderSyncWorker::class)
            );
        });
        $containerBuilder->registerFactory(ClientSecretProviderInterface::class, function ($container) {
            return new ClientSecretProvider(
                $container->get(PluginConfiguration::class)
            );
        });

        $this->container = $containerBuilder->build();
    }

    /**
     * @return ContainerFactory
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new ContainerFactory();
        }
        return self::$instance;
    }

    /**
     * Forces reload of the ContainerFactory on next request.
     * @return void
     */
    public static function resetContainer()
    {
        self::$instance = null;
    }
}
