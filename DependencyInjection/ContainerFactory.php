<?php

namespace Axytos\KaufAufRechnung_OXID5\DependencyInjection;

use Axytos\ECommerce\Abstractions\ApiHostProviderInterface;
use Axytos\ECommerce\Abstractions\ApiKeyProviderInterface;
use Axytos\ECommerce\Abstractions\FallbackModeConfigurationInterface;
use Axytos\ECommerce\Abstractions\PaymentMethodConfigurationInterface;
use Axytos\ECommerce\Abstractions\UserAgentInfoProviderInterface;
use Axytos\ECommerce\AxytosECommerceClient;
use Axytos\ECommerce\Clients\Checkout\CheckoutClientInterface;
use Axytos\ECommerce\Clients\ErrorReporting\ErrorReportingClientInterface;
use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\ECommerce\DataMapping\DtoArrayMapper;
use Axytos\ECommerce\DependencyInjection\Container;
use Axytos\ECommerce\DependencyInjection\ContainerBuilder;
use Axytos\ECommerce\Logging\LoggerAdapterInterface;
use Axytos\ECommerce\OrderSync\HashAlgorithmInterface;
use Axytos\ECommerce\OrderSync\OrderHashCalculator;
use Axytos\ECommerce\OrderSync\OrderSyncItemFactory;
use Axytos\ECommerce\OrderSync\OrderSyncItemRepository;
use Axytos\ECommerce\OrderSync\OrderSyncWorker;
use Axytos\ECommerce\OrderSync\SHA256HashAlgorithm;
use Axytos\ECommerce\OrderSync\ShopSystemOrderRepositoryInterface;
use Axytos\ECommerce\PackageInfo\ComposerPackageInfoProvider;
use Axytos\KaufAufRechnung_OXID5\Client\ApiHostProvider;
use Axytos\KaufAufRechnung_OXID5\Client\ApiKeyProvider;
use Axytos\KaufAufRechnung_OXID5\Client\FallbackModeConfiguration;
use Axytos\KaufAufRechnung_OXID5\Client\Oxid5ShopVersionProvider;
use Axytos\KaufAufRechnung_OXID5\Client\PaymentMethodConfiguration;
use Axytos\KaufAufRechnung_OXID5\Client\UserAgentInfoProvider;
use Axytos\KaufAufRechnung_OXID5\Configuration\PluginConfiguration;
use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContextFactory;
use Axytos\KaufAufRechnung_OXID5\Core\OrderCheckProcessStateMachine;
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
        $containerBuilder->registerFactory(OrderCheckProcessStateMachine::class, function () {
            return new OrderCheckProcessStateMachine();
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
                $container->get(CreateInvoiceTaxGroupDtoCollectionFactory::class)
            );
        });
        $containerBuilder->registerFactory(CreateInvoiceBasketPositionDtoCollectionFactory::class, function ($container) {
            return new CreateInvoiceBasketPositionDtoCollectionFactory(
                $container->get(CreateInvoiceBasketPositionDtoFactory::class)
            );
        });
        $containerBuilder->registerFactory(CreateInvoiceBasketPositionDtoFactory::class, function ($container) {
            return new CreateInvoiceBasketPositionDtoFactory(
                $container->get(ShippingCostCalculator::class)
            );
        });
        $containerBuilder->registerFactory(CreateInvoiceTaxGroupDtoCollectionFactory::class, function ($container) {
            return new CreateInvoiceTaxGroupDtoCollectionFactory(
                $container->get(CreateInvoiceTaxGroupDtoFactory::class)
            );
        });
        $containerBuilder->registerFactory(CreateInvoiceTaxGroupDtoFactory::class, function ($container) {
            return new CreateInvoiceTaxGroupDtoFactory(
                $container->get(ShippingCostCalculator::class)
            );
        });
        $containerBuilder->registerFactory(CustomerDataDtoFactory::class, function () {
            return new CustomerDataDtoFactory();
        });
        $containerBuilder->registerFactory(InvoiceAddressDtoFactory::class, function () {
            return new InvoiceAddressDtoFactory();
        });
        $containerBuilder->registerFactory(DeliveryAddressDtoFactory::class, function () {
            return new DeliveryAddressDtoFactory();
        });
        $containerBuilder->registerFactory(BasketDtoFactory::class, function ($container) {
            return new BasketDtoFactory(
                $container->get(BasketPositionDtoCollectionFactory::class)
            );
        });
        $containerBuilder->registerFactory(BasketPositionDtoCollectionFactory::class, function ($container) {
            return new BasketPositionDtoCollectionFactory(
                $container->get(BasketPositionDtoFactory::class)
            );
        });
        $containerBuilder->registerFactory(BasketPositionDtoFactory::class, function ($container) {
            return new BasketPositionDtoFactory(
                $container->get(ShippingCostCalculator::class)
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
        $containerBuilder->registerFactory(OrderRepository::class, function ($container) {
            return new OrderRepository();
        });
        $containerBuilder->registerFactory(ShopSystemOrderFactory::class, function ($container) {
            return new ShopSystemOrderFactory(
                $container->get(InvoiceOrderContextFactory::class),
                $container->get(OrderHashCalculator::class)
            );
        });
        $containerBuilder->registerFactory(ShopSystemOrderRepositoryInterface::class, function ($container) {
            return new ShopSystemOrderRepository(
                $container->get(OrderRepository::class),
                $container->get(ShopSystemOrderFactory::class)
            );
        });
        $containerBuilder->registerFactory(OrderSyncItemFactory::class, function ($container) {
            return new OrderSyncItemFactory(
                $container->get(InvoiceClientInterface::class),
                $container->get(ErrorReportingClientInterface::class),
                $container->get(LoggerAdapterInterface::class)
            );
        });
        $containerBuilder->registerFactory(OrderSyncItemRepository::class, function ($container) {
            return new OrderSyncItemRepository(
                $container->get(ShopSystemOrderRepositoryInterface::class),
                $container->get(OrderSyncItemFactory::class)
            );
        });
        $containerBuilder->registerFactory(OrderSyncWorker::class, function ($container) {
            return new OrderSyncWorker(
                $container->get(OrderSyncItemRepository::class),
                $container->get(LoggerAdapterInterface::class)
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
        $containerBuilder->registerFactory(OrderHashCalculator::class, function ($container) {
            return new OrderHashCalculator(
                $container->get(HashAlgorithmInterface::class),
                $container->get(DtoArrayMapper::class)
            );
        });
        $containerBuilder->registerFactory(HashAlgorithmInterface::class, function ($container) {
            return new SHA256HashAlgorithm();
        });
        $containerBuilder->registerFactory(DtoArrayMapper::class, function ($container) {
            return new DtoArrayMapper();
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
