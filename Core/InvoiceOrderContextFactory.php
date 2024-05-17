<?php

namespace Axytos\KaufAufRechnung_OXID5\Core;

use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContext;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CustomerDataDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\DeliveryAddressDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\InvoiceAddressDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\ShippingBasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\LogisticianCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\TrackingIdCalculator;
use oxOrder;

class InvoiceOrderContextFactory
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\CustomerDataDtoFactory
     */
    private $customerDataDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\DeliveryAddressDtoFactory
     */
    private $deliveryAddressDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\InvoiceAddressDtoFactory
     */
    private $invoiceAddressDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\BasketDtoFactory
     */
    private $basketDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketDtoFactory
     */
    private $createInvoiceBasketDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\ShippingBasketPositionDtoCollectionFactory
     */
    private $shippingBasketPositionDtoCollectionFactory;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\ValueCalculation\TrackingIdCalculator
     */
    private $trackingIdCalculator;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\ValueCalculation\LogisticianCalculator
     */
    private $logisticianCalculator;

    public function __construct(
        CustomerDataDtoFactory $customerDataDtoFactory,
        InvoiceAddressDtoFactory $invoiceAddressDtoFactory,
        DeliveryAddressDtoFactory $deliveryAddressDtoFactory,
        BasketDtoFactory $basketDtoFactory,
        CreateInvoiceBasketDtoFactory $createInvoiceBasketDtoFactory,
        ShippingBasketPositionDtoCollectionFactory $shippingBasketPositionDtoCollectionFactory,
        TrackingIdCalculator $trackingIdCalculator,
        LogisticianCalculator $logisticianCalculator
    ) {
        $this->customerDataDtoFactory = $customerDataDtoFactory;
        $this->invoiceAddressDtoFactory = $invoiceAddressDtoFactory;
        $this->deliveryAddressDtoFactory = $deliveryAddressDtoFactory;
        $this->basketDtoFactory = $basketDtoFactory;
        $this->createInvoiceBasketDtoFactory = $createInvoiceBasketDtoFactory;
        $this->shippingBasketPositionDtoCollectionFactory = $shippingBasketPositionDtoCollectionFactory;
        $this->trackingIdCalculator = $trackingIdCalculator;
        $this->logisticianCalculator = $logisticianCalculator;
    }

    /**
     * @param oxOrder $order
     * @return \Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface&\Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContext
     */
    public function getInvoiceOrderContext(
        $order
    ) {
        return new InvoiceOrderContext($order, $this->customerDataDtoFactory, $this->invoiceAddressDtoFactory, $this->deliveryAddressDtoFactory, $this->basketDtoFactory, $this->createInvoiceBasketDtoFactory, $this->shippingBasketPositionDtoCollectionFactory, $this->trackingIdCalculator, $this->logisticianCalculator);
    }
}
