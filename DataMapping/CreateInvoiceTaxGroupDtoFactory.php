<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator;

class CreateInvoiceTaxGroupDtoFactory
{
    /**
     * @var ShippingCostCalculator
     */
    private $shippingCostCalculator;

    /**
     * @var VoucherDiscountCalculator
     */
    private $voucherDiscountCalculator;

    public function __construct(
        ShippingCostCalculator $shippingCostCalculator,
        VoucherDiscountCalculator $voucherDiscountCalculator
    ) {
        $this->shippingCostCalculator = $shippingCostCalculator;
        $this->voucherDiscountCalculator = $voucherDiscountCalculator;
    }

    /**
     * @param \oxOrderArticle $orderArticle
     *
     * @return CreateInvoiceTaxGroupDto
     */
    public function create($orderArticle)
    {
        $brutPrice = floatval($orderArticle->getFieldData('oxbrutprice'));
        $netPrice = floatval($orderArticle->getFieldData('oxnetprice'));

        $taxGroup = new CreateInvoiceTaxGroupDto();
        $taxGroup->valueToTax = $netPrice;
        $taxGroup->total = round($brutPrice - $netPrice, 2);
        $taxGroup->taxPercent = floatval($orderArticle->getFieldData('oxvat'));

        return $taxGroup;
    }

    /**
     * @param \oxOrder $order
     *
     * @return CreateInvoiceTaxGroupDto
     */
    public function createShippingPosition($order)
    {
        $grossDeliveryCosts = floatval($order->getFieldData('oxdelcost'));
        $deliveryTax = floatval($order->getFieldData('oxdelvat'));

        $taxGroup = new CreateInvoiceTaxGroupDto();
        $taxGroup->valueToTax = $this->shippingCostCalculator->calculateNetPrice($grossDeliveryCosts, $deliveryTax);
        $taxGroup->total = round($grossDeliveryCosts - $taxGroup->valueToTax, 2);
        $taxGroup->taxPercent = $deliveryTax;

        return $taxGroup;
    }

    /**
     * @param \oxOrder $order
     *
     * @return CreateInvoiceTaxGroupDto|null
     */
    public function createVoucherPosition($order)
    {
        $isB2B = boolval($order->getFieldData('oxisnettomode'));

        $totalVoucherDiscountForOrder = $this->voucherDiscountCalculator->calculate($order);

        if (0.0 === $totalVoucherDiscountForOrder) {
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
