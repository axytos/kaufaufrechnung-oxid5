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

        if (strval($order->getFieldData("oxdelstreet")) !== '') {
            $deliveryAddressDto->addressLine1 = $order->getFieldData("oxdelstreet") . " " . $order->getFieldData("oxdelstreetnr");
        } else {
            $deliveryAddressDto->addressLine1 = $order->getFieldData("oxbillstreet") . " " . $order->getFieldData("oxbillstreetnr");
        }

        $deliveryAddressDto->city = $this->getStringFieldOrAlternative($order, 'oxdelcity', 'oxbillcity');
        $deliveryAddressDto->company = $this->getStringFieldOrAlternative($order, 'oxdelcompany', 'oxbillcompany');
        $deliveryAddressDto->firstname = $this->getStringFieldOrAlternative($order, 'oxdelfname', 'oxbillfname');
        $deliveryAddressDto->lastname = $this->getStringFieldOrAlternative($order, 'oxdellname', 'oxbilllname');
        $deliveryAddressDto->salutation = $this->getStringFieldOrAlternative($order, 'oxdelsal', 'oxbillsal');
        $deliveryAddressDto->vatId = $this->getStringFieldOrAlternative($order, 'oxdelustid', 'oxbillustid');
        $deliveryAddressDto->zipCode = $this->getStringFieldOrAlternative($order, 'oxdelzip', 'oxbillzip');

        $countryId = $this->getStringFieldOrAlternative($order, 'oxdelcountryid', 'oxbillcountryid');
        if ($countryId !== "") {
            $db = oxDb::getDb();
            $country = $db->getOne(
                "SELECT oxcountry.oxisoalpha2 FROM oxcountry WHERE oxid = ?",
                [$countryId]
            );

            $deliveryAddressDto->country = strval($country) !== '' ? strval($country) : null;
        }

        $stateId = $this->getStringFieldOrAlternative($order, 'oxdelstateid', 'oxbillstateid');
        if ($stateId !== "") {
            $db = oxDb::getDb();
            $state = $db->getOne(
                "SELECT oxstates.oxtitle FROM oxstates WHERE oxid = ?",
                [$stateId]
            );

            $deliveryAddressDto->region = strval($state) !== '' ? strval($state) : null;
        }

        return $deliveryAddressDto;
    }

    /**
     * @param oxOrder $order
     * @param string $fieldName
     * @param string $altFieldName
     * @return string|null
     */
    private function getStringFieldOrAlternative($order, $fieldName, $altFieldName)
    {
        $fieldValue = $this->getStringField($order, $fieldName);
        if (is_null($fieldValue)) {
            return $this->getStringField($order, $altFieldName);
        }
        return $fieldValue;
    }

    /**
     * @param oxOrder $order
     * @param string $fieldName
     * @return string|null
     */
    private function getStringField($order, $fieldName)
    {
        $fieldValue = $order->getFieldData($fieldName);
        if (!is_null($fieldValue)) {
            return strval($fieldValue);
        }
        return null;
    }
}
