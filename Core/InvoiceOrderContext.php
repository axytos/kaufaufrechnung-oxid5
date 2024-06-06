<?php

namespace Axytos\KaufAufRechnung_OXID5\Core;

use Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface;
use Axytos\ECommerce\DataTransferObjects\RefundBasketDto;
use Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDtoCollection;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CustomerDataDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\DeliveryAddressDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\InvoiceAddressDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\ShippingBasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\LogisticianCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\TrackingIdCalculator;
use DateTimeImmutable;
use DateTimeInterface;
use oxField;
use oxOrder;

class InvoiceOrderContext implements InvoiceOrderContextInterface
{
    /**
     * @var oxOrder
     */
    private $order;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\CustomerDataDtoFactory
     */
    private $customerDataDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\InvoiceAddressDtoFactory
     */
    private $invoiceAddressDtoFactory;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\DeliveryAddressDtoFactory
     */
    private $deliveryAddressDtoFactory;
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
        oxOrder $order,
        CustomerDataDtoFactory $customerDataDtoFactory,
        InvoiceAddressDtoFactory $invoiceAddressDtoFactory,
        DeliveryAddressDtoFactory $deliveryAddressDtoFactory,
        BasketDtoFactory $basketDtoFactory,
        CreateInvoiceBasketDtoFactory $createInvoiceBasketDtoFactory,
        ShippingBasketPositionDtoCollectionFactory $shippingBasketPositionDtoCollectionFactory,
        TrackingIdCalculator $trackingIdCalculator,
        LogisticianCalculator $logisticianCalculator
    ) {
        $this->order = $order;
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
     * @return string
     */
    public function getOrderNumber()
    {
        return strval($this->order->getFieldData('oxordernr'));
    }

    /**
     * @return string
     */
    public function getOrderInvoiceNumber()
    {
        /** @var string */
        return $this->order->getFieldData("oxbillnr");
    }

    /**
     * @return \DateTimeInterface
     */
    public function getOrderDateTime()
    {
        /** @phpstan-ignore-next-line */
        return DateTimeImmutable::createFromFormat('Y-m-d G:i:s', $this->order->getFieldData("oxorderdate"));
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\CustomerDataDto
     */
    public function getPersonalData()
    {
        return $this->customerDataDtoFactory->create($this->order);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\InvoiceAddressDto
     */
    public function getInvoiceAddress()
    {
        return $this->invoiceAddressDtoFactory->create($this->order);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\DeliveryAddressDto
     */
    public function getDeliveryAddress()
    {
        return $this->deliveryAddressDtoFactory->create($this->order);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\BasketDto
     */
    public function getBasket()
    {
        return $this->basketDtoFactory->create($this->order);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\RefundBasketDto
     */
    public function getRefundBasket()
    {
        return new RefundBasketDto();
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto
     */
    public function getCreateInvoiceBasket()
    {
        return $this->createInvoiceBasketDtoFactory->create($this->order);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDtoCollection
     */
    public function getShippingBasketPositions()
    {
        return $this->shippingBasketPositionDtoCollectionFactory->create($this->order);
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\ReturnPositionModelDtoCollection
     */
    public function getReturnPositions()
    {
        return new ReturnPositionModelDtoCollection();
    }

    /**
     * @return mixed[]
     */
    public function getPreCheckResponseData()
    {
        /** @phpstan-ignore-next-line */
        return unserialize(base64_decode($this->order->oxorder__axytoskaufaufrechnungorderprecheckresult->value));
    }

    /**
     * @param mixed[] $data
     * @return void
     */
    public function setPreCheckResponseData($data)
    {
        /** @phpstan-ignore-next-line */
        $this->order->oxorder__axytoskaufaufrechnungorderprecheckresult = new oxField(base64_encode(serialize($data)));
        $this->order->save();
    }

    /**
     * @return float
     */
    public function getDeliveryWeight()
    {
        // for now delivery weight is not important for risk evaluation
        // because different shop systems don't always provide the necessary
        // information to accurately the exact delivery weight for each delivery
        // we decided to return 0 as constant delivery weight
        return 0;
    }

    /**
     * @return string[]
     */
    public function getTrackingIds()
    {
        return $this->trackingIdCalculator->calculate($this->order);
    }

    /**
     * @return string
     */
    public function getLogistician()
    {
        return $this->logisticianCalculator->calculate($this->order);
    }
}
