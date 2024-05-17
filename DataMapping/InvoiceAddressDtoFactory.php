<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\InvoiceAddressDto;
use Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository;
use oxOrder;

class InvoiceAddressDtoFactory
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository
     */
    private $orderRepository;

    /**
     * @param \Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository $orderRepository
     * @return void
     */
    public function __construct($orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\InvoiceAddressDto
     */
    public function create($order)
    {
        $invoiceAddressDto = new InvoiceAddressDto();

        $invoiceAddressDto->addressLine1 = $order->getFieldData("oxbillstreet") . " " . $order->getFieldData("oxbillstreetnr");
        $invoiceAddressDto->city = strval($order->getFieldData("oxbillcity")) !== '' ? strval($order->getFieldData("oxbillcity")) : null;
        $invoiceAddressDto->company = strval($order->getFieldData("oxbillcompany")) !== '' ? strval($order->getFieldData("oxbillcompany")) : null;
        $invoiceAddressDto->firstname = strval($order->getFieldData("oxbillfname")) !== '' ? strval($order->getFieldData("oxbillfname")) : null;
        $invoiceAddressDto->lastname = strval($order->getFieldData("oxbilllname")) !== '' ? strval($order->getFieldData("oxbilllname")) : null;
        $invoiceAddressDto->salutation = strval($order->getFieldData("oxbillsal")) !== '' ? strval($order->getFieldData("oxbillsal")) : null;
        $invoiceAddressDto->vatId = strval($order->getFieldData("oxbillustid")) !== '' ? strval($order->getFieldData("oxbillustid")) : null;
        $invoiceAddressDto->zipCode = strval($order->getFieldData("oxbillzip")) !== '' ? strval($order->getFieldData("oxbillzip")) : null;

        $countryId = $order->getFieldData("oxbillcountryid");
        if ($countryId !== "") {
            $invoiceAddressDto->country = $this->orderRepository->findInvoiceAddressCountryById($countryId);
        }

        $stateId = $order->getFieldData("oxbillstateid");
        if ($stateId !== "") {
            $invoiceAddressDto->region = $this->orderRepository->findInvoiceAddressStateById($stateId);
        }

        return $invoiceAddressDto;
    }
}
