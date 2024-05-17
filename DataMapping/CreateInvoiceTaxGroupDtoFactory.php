<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;

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
     * @param \oxOrderArticle $orderArticle
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
     * @param \oxOrder $order
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
     * @param \oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto|null
     */
    public function createVoucherPosition($order)
    {
        $isB2B = boolval($order->getFieldData('oxisnettomode'));

        // the total monetary value of all applied vouchers
        $totalVoucherDiscountForOrder = -1 * $order->getFieldData("oxvoucherdiscount");

        if ($totalVoucherDiscountForOrder === 0.0) {
            return null;
        }

        $position = new CreateInvoiceTaxGroupDto();
        $position->taxPercent = 0;

        if ($isB2B) {
            // voucher is subtracted from net backet value,
            // so it is a value to tax
            $position->total = 0;
            $position->valueToTax = $totalVoucherDiscountForOrder;
        } else {
            // voucher is substracted from gross basket value,
            // so it is total value for the whole order
            $position->total = $totalVoucherDiscountForOrder;
            $position->valueToTax = 0;
        }

        return $position;
    }
}
