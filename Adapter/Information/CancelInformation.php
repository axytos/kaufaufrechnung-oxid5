<?php

namespace Axytos\KaufAufRechnung_OXID5\Adapter\Information;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\CancelInformationInterface;
use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContext;

class CancelInformation implements CancelInformationInterface
{
    /**
     * @var InvoiceOrderContext
     */
    private $invoiceOrderContext;

    public function __construct(InvoiceOrderContext $invoiceOrderContext)
    {
        $this->invoiceOrderContext = $invoiceOrderContext;
    }

    public function getOrderNumber()
    {
        return $this->invoiceOrderContext->getOrderNumber();
    }
}
