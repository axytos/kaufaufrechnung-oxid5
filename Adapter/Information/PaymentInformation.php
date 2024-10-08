<?php

namespace Axytos\KaufAufRechnung_OXID5\Adapter\Information;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\PaymentInformationInterface;
use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContext;

/**
 * payment callbacks are currently not a supported feature for oxid.
 */
class PaymentInformation implements PaymentInformationInterface
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
