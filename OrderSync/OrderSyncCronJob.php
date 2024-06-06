<?php

namespace Axytos\KaufAufRechnung_OXID5\OrderSync;

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\ECommerce\Logging\LoggerAdapterInterface;
use Axytos\ECommerce\OrderSync\OrderSyncWorker;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;

class OrderSyncCronJob
{
  /**
   * @var PluginConfigurationValidator
   */
    private $pluginConfigurationValidator;

  /**
   * @var OrderSyncWorker
   */
    private $orderSyncWorker;

  /**
   * @var LoggerAdapterInterface
   */
    private $logger;

  /**
   * @var ErrorHandler
   */
    private $errorHandler;

    public function __construct(
        PluginConfigurationValidator $pluginConfigurationValidator,
        OrderSyncWorker $orderSyncWorker,
        LoggerAdapterInterface $logger,
        ErrorHandler $errorHandler
    ) {
        $this->pluginConfigurationValidator = $pluginConfigurationValidator;
        $this->orderSyncWorker = $orderSyncWorker;
        $this->logger = $logger;
        $this->errorHandler = $errorHandler;
    }

  /**
   * @return void
   */
    public function execute()
    {
        try {
            $this->logger->info('CronJob Order Sync started');

            if ($this->pluginConfigurationValidator->isInvalid()) {
                $this->logger->info('CronJob Order Sync aborted: invalid config');
                return;
            }

            $this->orderSyncWorker->sync();
            $this->logger->info('CronJob Order Sync succeeded');
        } catch (\Throwable $th) {
            $this->logger->error('CronJob Order Sync failed');
            $this->errorHandler->handle($th);
        } catch (\Exception $th) { // @phpstan-ignore-line because of php5 compatibility
            $this->logger->error('CronJob Order Sync failed');
            $this->errorHandler->handle($th);
        }
    }
}
