<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\DeliveryAddressDto;
use oxDb;
use oxOrder;

class DeliveryAddressDtoFactory
{
    /**
     * @param oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\DeliveryAddressDto
     */
    public function create($order)
    {
        $deliveryAddressDto = new DeliveryAddressDto();

        if ($order->getFieldData("oxdelstreet")) {
            $deliveryAddressDto->addressLine1 = $order->getFieldData("oxdelstreet") . " " . $order->getFieldData("oxdelstreetnr");
        } else {
            $deliveryAddressDto->addressLine1 = $order->getFieldData("oxbillstreet") . " " . $order->getFieldData("oxbillstreetnr");
        }

        $deliveryAddressDto->city = strval($order->getFieldData("oxdelcity") ?: $order->getFieldData("oxbillcity")) ?: null;
        $deliveryAddressDto->company = strval($order->getFieldData("oxdelcompany") ?: $order->getFieldData("oxbillcompany")) ?: null;
        $deliveryAddressDto->firstname = strval($order->getFieldData("oxdelfname") ?: $order->getFieldData("oxbillfname")) ?: null;
        $deliveryAddressDto->lastname = strval($order->getFieldData("oxdellname") ?: $order->getFieldData("oxbilllname")) ?: null;
        $deliveryAddressDto->salutation = strval($order->getFieldData("oxdelsal") ?: $order->getFieldData("oxbillsal")) ?: null;
        $deliveryAddressDto->vatId = strval($order->getFieldData("oxdelustid") ?: $order->getFieldData("oxbillustid")) ?: null;
        $deliveryAddressDto->zipCode = strval($order->getFieldData("oxdelzip") ?: $order->getFieldData("oxbillzip")) ?: null;

        $countryId = $order->getFieldData("oxdelcountryid") ?: $order->getFieldData("oxbillcountryid");
        if ($countryId != "") {
            $db = oxDb::getDb();
            $country = $db->getOne(
                "SELECT oxcountry.oxisoalpha2 FROM oxcountry WHERE oxid = ?",
                [$countryId]
            );

            $deliveryAddressDto->country = strval($country) ?: null;
        }

        $stateId = $order->getFieldData("oxdelstateid") ?: $order->getFieldData("oxbillstateid");
        if ($stateId != "") {
            $db = oxDb::getDb();
            $state = $db->getOne(
                "SELECT oxstates.oxtitle FROM oxstates WHERE oxid = ?",
                [$stateId]
            );

            $deliveryAddressDto->region = strval($state) ?: null;
        }

        return $deliveryAddressDto;
    }
}
