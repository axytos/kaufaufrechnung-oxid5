<?php

namespace Axytos\KaufAufRechnung_OXID5\Logging;

use Axytos\ECommerce\Logging\LoggerAdapterInterface;

class LoggerAdapter implements LoggerAdapterInterface
{
    use OxidLoggerFactoryTrait;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct()
    {
        $this->logger = $this->getLogger();
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function error($message)
    {
        $this->logger->error($message);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function warning($message)
    {
        $this->logger->warning($message);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function info($message)
    {
        $this->logger->info($message);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function debug($message)
    {
        $this->logger->debug($message);
    }
}
