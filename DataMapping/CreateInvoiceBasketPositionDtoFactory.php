<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator;

class CreateInvoiceBasketPositionDtoFactory
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
     * @return CreateInvoiceBasketPositionDto
     */
    public function create($orderArticle)
    {
        $position = new CreateInvoiceBasketPositionDto();
        $position->productId = strval($orderArticle->getFieldData('oxartnum'));
        $position->productName = strval($orderArticle->getFieldData('oxtitle'));
        $position->quantity = floatval($orderArticle->getFieldData('oxamount'));
        $position->taxPercent = floatval($orderArticle->getFieldData('oxvat'));
        $position->netPricePerUnit = floatval($orderArticle->getFieldData('oxnprice'));
        $position->grossPricePerUnit = floatval($orderArticle->getFieldData('oxbprice'));
        $position->netPositionTotal = floatval($orderArticle->getFieldData('oxnetprice'));
        $position->grossPositionTotal = floatval($orderArticle->getFieldData('oxbrutprice'));

        return $position;
    }

    /**
     * @param \oxOrder $order
     *
     * @return CreateInvoiceBasketPositionDto
     */
    public function createShippingPosition($order)
    {
        $grossDeliveryCosts = floatval($order->getFieldData('oxdelcost'));
        $deliveryTax = floatval($order->getFieldData('oxdelvat'));

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
     * @param \oxOrder                                                               $order
     * @param \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto[] $positions
     *
     * @return CreateInvoiceBasketPositionDto|null
     */
    public function createVoucherPosition($order, $positions)
    {
        $isB2B = boolval($order->getFieldData('oxisnettomode'));

        $totalVoucherDiscountForOrder = $this->voucherDiscountCalculator->calculate($order);

        if (0.0 === $totalVoucherDiscountForOrder) {
            return null;
        }

        $position = new CreateInvoiceBasketPositionDto();
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
