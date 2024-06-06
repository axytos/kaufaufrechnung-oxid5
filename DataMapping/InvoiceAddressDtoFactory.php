<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\InvoiceAddressDto;
use oxDb;
use oxOrder;

class InvoiceAddressDtoFactory
{
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
            $db = oxDb::getDb();
            $country = $db->getOne(
                "SELECT oxcountry.oxisoalpha2 FROM oxcountry WHERE oxid = ?",
                [$countryId]
            );

            $invoiceAddressDto->country = strval($country) !== '' ? strval($country) : null;
        }

        $stateId = $order->getFieldData("oxbillstateid");
        if ($stateId !== "") {
            $db = oxDb::getDb();
            $state = $db->getOne(
                "SELECT oxstates.oxtitle FROM oxstates WHERE oxid = ?",
                [$stateId]
            );

            $invoiceAddressDto->region = strval($state) !== '' ? strval($state) : null;
        }

        return $invoiceAddressDto;
    }
}
