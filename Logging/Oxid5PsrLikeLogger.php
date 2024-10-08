<?php

namespace Axytos\KaufAufRechnung_OXID5\Logging;

class Oxid5PsrLikeLogger
{
    /**
     * @var \oxUtils
     */
    private $utils;

    public function __construct()
    {
        $this->utils = \oxRegistry::getUtils();
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function error($message)
    {
        $this->logMessage('[Error]  ', $message);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function warning($message)
    {
        $this->logMessage('[Warning]', $message);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function info($message)
    {
        $this->logMessage('[Info]   ', $message);
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function debug($message)
    {
        $this->logMessage('[Debug]  ', $message);
    }

    /**
     * @param string $type
     * @param string $message
     *
     * @return void
     */
    private function logMessage($type, $message)
    {
        $now = new \DateTime();
        $logEntry = $now->format(\DateTime::ISO8601) . ' ' . $type . ' ' . $message . "\n";
        $this->utils->writeToLog(
            $logEntry,
            'axytos_kaufaufrechnung.log'
        );
    }
}
