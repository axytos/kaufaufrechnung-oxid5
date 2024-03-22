<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\BasketPositionDto;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use oxOrder;
use oxOrderArticle;

class BasketPositionDtoFactory
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
     * @param oxOrder $order
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
     * @param oxOrder $order
     * @param \Axytos\ECommerce\DataTransferObjects\BasketPositionDto[] $positions
     * @return \Axytos\ECommerce\DataTransferObjects\BasketPositionDto|null
     */
    public function createVoucherPosition($order, $positions)
    {
        $voucherDiscountGross = -floatval($order->getFieldData("oxvoucherdiscount"));
        if ($voucherDiscountGross === 0.0) {
            return null;
        }

        $netPosSum = array_sum(array_map(
            function (BasketPositionDto $dto) {
                return $dto->netPositionTotal;
            },
            $positions
        ));
        $voucherDiscountNet = round(floatval($order->getFieldData("oxtotalnetsum")) - $netPosSum, 2);
        if (!is_finite($voucherDiscountNet)) {
            $voucherDiscountNet = 0;
        }

        $voucherTaxPercent = round((($voucherDiscountGross / $voucherDiscountNet) - 1) * 100);
        if (!is_finite($voucherTaxPercent)) {
            $voucherTaxPercent = 0;
        }

        $position = new BasketPositionDto();
        $position->productId = 'oxvoucherdiscount';
        $position->productName = 'Voucher';
        $position->quantity = 1;
        $position->grossPositionTotal = $voucherDiscountGross;
        $position->netPositionTotal = $voucherDiscountNet;
        $position->taxPercent = $voucherTaxPercent;
        $position->netPricePerUnit = $position->netPositionTotal;
        $position->grossPricePerUnit = $position->grossPositionTotal;
        return $position;
    }
}
