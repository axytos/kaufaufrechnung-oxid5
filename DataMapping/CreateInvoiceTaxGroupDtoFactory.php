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
        $brutPrice = floatval($orderArticle->getFieldData("oxbrutprice"));
        $netPrice = floatval($orderArticle->getFieldData("oxnetprice"));

        $taxGroup = new CreateInvoiceTaxGroupDto();
        $taxGroup->valueToTax = $netPrice;
        $taxGroup->total = round($brutPrice - $netPrice, 2);
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

    /**
     * @param oxOrder $order
     * @param \Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto[] $taxGroups
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto|null
     */
    public function createVoucherPosition($order, $taxGroups)
    {
        $voucherDiscountGross = -floatval($order->getFieldData("oxvoucherdiscount"));
        if ($voucherDiscountGross === 0.0) {
            return null;
        }

        $valueToTaxSum = array_sum(array_map(
            function (CreateInvoiceTaxGroupDto $dto) {
                return $dto->valueToTax;
            },
            $taxGroups
        ));
        $voucherDiscountNet = round(floatval($order->getFieldData("oxtotalnetsum")) - $valueToTaxSum, 2);
        if (!is_finite($voucherDiscountNet)) {
            $voucherDiscountNet = 0;
        }

        $voucherTaxPercent = round((($voucherDiscountGross / $voucherDiscountNet) - 1) * 100);
        if (!is_finite($voucherTaxPercent)) {
            $voucherTaxPercent = 0;
        }

        $position = new CreateInvoiceTaxGroupDto();
        $position->valueToTax = $voucherDiscountNet;
        $position->taxPercent = $voucherTaxPercent;
        $position->total = round($voucherDiscountGross - $voucherDiscountNet, 2);
        return $position;
    }
}
