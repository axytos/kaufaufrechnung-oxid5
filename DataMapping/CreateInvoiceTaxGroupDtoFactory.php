<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use oxOrder;
use oxOrderArticle;

class CreateInvoiceTaxGroupDtoFactory
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator
     */
    private $shippingCostCalculator;

    public function __construct(
        ShippingCostCalculator $shippingCostCalculator
    ) {
        $this->shippingCostCalculator = $shippingCostCalculator;
    }

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
        $grossDeliveryCosts = floatval($order->getFieldData("oxdelcost"));
        $deliveryTax = floatval($order->getFieldData("oxdelvat"));

        $taxGroup = new CreateInvoiceTaxGroupDto();
        $taxGroup->valueToTax = $this->shippingCostCalculator->calculateNetPrice($grossDeliveryCosts, $deliveryTax);
        $taxGroup->total = round($grossDeliveryCosts - $taxGroup->valueToTax, 2);
        $taxGroup->taxPercent = $deliveryTax;

        return $taxGroup;
    }
}
