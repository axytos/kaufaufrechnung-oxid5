<?php

namespace Axytos\KaufAufRechnung_OXID5\ErrorReporting;

use Axytos\ECommerce\Clients\ErrorReporting\ErrorReportingClientInterface;
use Throwable;

class ErrorHandler
{
    /**
     * @var \Axytos\ECommerce\Clients\ErrorReporting\ErrorReportingClientInterface
     */
    private $errorReportingClient;

    public function __construct(ErrorReportingClientInterface $errorReportingClient)
    {
        $this->errorReportingClient = $errorReportingClient;
    }

    /**
     * @param \Throwable $throwable
     * @return void
     */
    public function handle($throwable)
    {
        $this->errorReportingClient->reportError($throwable);
    }
}
