<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\BasketPositionDto;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator;

class BasketPositionDtoFactory
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator
     */
    private $shippingCostCalculator;

    /**
     * @var \Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator
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
     * @return \Axytos\ECommerce\DataTransferObjects\BasketPositionDto
     */
    public function create($orderArticle)
    {
        $position = new BasketPositionDto();
        $position->productId = strval($orderArticle->getFieldData("oxartnum"));
        $position->productName = strval($orderArticle->getFieldData("oxtitle"));
        $position->quantity = intval($orderArticle->getFieldData("oxamount"));
        $position->grossPositionTotal = floatval($orderArticle->getFieldData("oxbrutprice"));
        $position->netPositionTotal = floatval($orderArticle->getFieldData("oxnetprice"));
        $position->taxPercent = floatval($orderArticle->getFieldData("oxvat"));
        $position->netPricePerUnit = floatval($orderArticle->getFieldData("oxnprice"));
        $position->grossPricePerUnit = floatval($orderArticle->getFieldData("oxbprice"));

        return $position;
    }

    /**
     * @param \oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\BasketPositionDto
     */
    public function createShippingPosition($order)
    {
        $grossDeliveryCosts = floatval($order->getFieldData("oxdelcost"));
        $deliveryTax = floatval($order->getFieldData("oxdelvat"));

        $position = new BasketPositionDto();
        $position->productId = '0';
        $position->productName = 'Shipping';
        $position->quantity = 1;
        $position->grossPositionTotal = $grossDeliveryCosts;
        $position->netPositionTotal = $this->shippingCostCalculator->calculateNetPrice($grossDeliveryCosts, $deliveryTax);
        $position->taxPercent = $deliveryTax;
        $position->netPricePerUnit = $position->netPositionTotal;
        $position->grossPricePerUnit = $position->grossPositionTotal;
        return $position;
    }

    /**
     * @param \oxOrder $order
     * @param \Axytos\ECommerce\DataTransferObjects\BasketPositionDto[] $positions
     * @return \Axytos\ECommerce\DataTransferObjects\BasketPositionDto|null
     */
    public function createVoucherPosition($order, $positions)
    {
        $isB2B = boolval($order->getFieldData('oxisnettomode'));

        $totalVoucherDiscountForOrder = $this->voucherDiscountCalculator->calculate($order);

        if ($totalVoucherDiscountForOrder === 0.0) {
            return null;
        }

        $position = new BasketPositionDto();
        $position->productId = 'oxvoucherdiscount';
        $position->productName = 'Voucher';
        $position->quantity = 1;
        $position->taxPercent = 0;

        if ($isB2B) {
            $position->grossPositionTotal = 0;
            $position->netPositionTotal = $totalVoucherDiscountForOrder;
        } else {
            $position->grossPositionTotal = $totalVoucherDiscountForOrder;
            $position->netPositionTotal = 0;
        }

        $position->netPricePerUnit = $position->netPositionTotal;
        $position->grossPricePerUnit = $position->grossPositionTotal;
        return $position;
    }
}
