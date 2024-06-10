<?php

namespace Axytos\KaufAufRechnung_OXID5\Adapter\Information;

use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\RefundInformationInterface;
use Axytos\KaufAufRechnung_OXID5\Adapter\Information\Refund\Basket;

class RefundInformation implements RefundInformationInterface
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContext
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

    public function getInvoiceNumber()
    {
        return $this->invoiceOrderContext->getOrderInvoiceNumber();
    }

    public function getBasket()
    {
        $dto = $this->invoiceOrderContext->getRefundBasket();
        return new Basket($dto);
    }
}
