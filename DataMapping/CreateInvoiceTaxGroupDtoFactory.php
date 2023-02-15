<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto;
use oxOrder;
use oxOrderArticle;

class CreateInvoiceTaxGroupDtoFactory
{
    /**
     * @param oxOrderArticle $orderArticle
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto
     */
    public function create($orderArticle)
    {
        $taxGroup = new CreateInvoiceTaxGroupDto();
        $taxGroup->total = floatval($orderArticle->getFieldData("oxbrutprice"));
        $taxGroup->valueToTax = floatval($orderArticle->getFieldData("oxnetprice"));
        $taxGroup->taxPercent = floatval($orderArticle->getFieldData("oxvat"));

        return $taxGroup;
    }

    /**
     * @param oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto
     */
    public function createShippingPosition($order)
    {
        $taxGroup = new CreateInvoiceTaxGroupDto();
        $taxGroup->total = floatval($order->getFieldData("oxdelcost"));
        $taxGroup->valueToTax = round(floatval($order->getFieldData("oxdelcost")) * (1 - floatval($order->getFieldData("oxdelvat")) / 100), 2);
        $taxGroup->taxPercent = floatval($order->getFieldData("oxdelvat"));

        return $taxGroup;
    }
}
