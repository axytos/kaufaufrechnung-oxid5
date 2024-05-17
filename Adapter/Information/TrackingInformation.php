<?php

namespace Axytos\KaufAufRechnung_OXID5\Adapter\Information;

use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Information\TrackingInformationInterface;
use Axytos\KaufAufRechnung_OXID5\Adapter\Information\Tracking\DeliveryAddress;

class TrackingInformation implements TrackingInformationInterface
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

    public function getDeliveryWeight()
    {
        return $this->invoiceOrderContext->getDeliveryWeight();
    }

    public function getDeliveryMethod()
    {
        return $this->invoiceOrderContext->getLogistician();
    }

    public function getDeliveryAddress()
    {
        $dto = $this->invoiceOrderContext->getDeliveryAddress();
        return new DeliveryAddress($dto);
    }

    public function getTrackingIds()
    {
        return $this->invoiceOrderContext->getTrackingIds();
    }
}
