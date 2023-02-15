<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto;
use oxOrder;

class CreateInvoiceBasketDtoFactory
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketPositionDtoCollectionFactory
     */
    private $createInvoiceBasketPositionDtoCollectionFactory;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceTaxGroupDtoCollectionFactory
     */
    private $createInvoiceTaxGroupDtoCollectionFactory;

    public function __construct(
        CreateInvoiceBasketPositionDtoCollectionFactory $createInvoiceBasketPositionDtoCollectionFactory,
        CreateInvoiceTaxGroupDtoCollectionFactory $createInvoiceTaxGroupDtoCollectionFactory
    ) {
        $this->createInvoiceBasketPositionDtoCollectionFactory = $createInvoiceBasketPositionDtoCollectionFactory;
        $this->createInvoiceTaxGroupDtoCollectionFactory = $createInvoiceTaxGroupDtoCollectionFactory;
    }

    /**
     * @param oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto
     */
    public function create($order)
    {
        $basket = new CreateInvoiceBasketDto();
        $basket->positions = $this->createInvoiceBasketPositionDtoCollectionFactory->create($order);
        $basket->taxGroups = $this->createInvoiceTaxGroupDtoCollectionFactory->create($order);
        $basket->grossTotal = floatval($order->getFieldData("oxtotalbrutsum"));
        $basket->netTotal = floatval($order->getFieldData("oxtotalnetsum"));
        return $basket;
    }
}
