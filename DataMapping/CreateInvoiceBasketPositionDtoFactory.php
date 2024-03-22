<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use oxOrder;
use oxOrderArticle;

class CreateInvoiceBasketPositionDtoFactory
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
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto
     */
    public function create($orderArticle)
    {
        $position = new CreateInvoiceBasketPositionDto();
        $position->productId = strval($orderArticle->getFieldData("oxartnum"));
        $position->productName = strval($orderArticle->getFieldData("oxtitle"));
        $position->quantity = intval($orderArticle->getFieldData("oxamount"));
        $position->taxPercent = floatval($orderArticle->getFieldData("oxvat"));
        $position->netPricePerUnit = floatval($orderArticle->getFieldData("oxnprice"));
        $position->grossPricePerUnit = floatval($orderArticle->getFieldData("oxbprice"));
        $position->netPositionTotal = floatval($orderArticle->getFieldData("oxnetprice"));
        $position->grossPositionTotal = floatval($orderArticle->getFieldData("oxbrutprice"));

        return $position;
    }

    /**
     * @param oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto
     */
    public function createShippingPosition($order)
    {
        $grossDeliveryCosts = floatval($order->getFieldData("oxdelcost"));
        $deliveryTax = floatval($order->getFieldData("oxdelvat"));

        $position = new CreateInvoiceBasketPositionDto();
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
     * @param \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto[] $positions
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto|null
     */
    public function createVoucherPosition($order, $positions)
    {
        $voucherDiscountGross = -floatval($order->getFieldData("oxvoucherdiscount"));
        if ($voucherDiscountGross === 0.0) {
            return null;
        }

        $netPosSum = array_sum(array_map(
            function (CreateInvoiceBasketPositionDto $dto) {
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

        $position = new CreateInvoiceBasketPositionDto();
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
