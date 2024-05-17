<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\BasketDto;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;

class BasketDtoFactory
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\BasketPositionDtoCollectionFactory
     */
    private $basketPositionDtoCollectionFactory;

    /**
     * @var \Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator
     */
    private $shippingCostCalculator;

    public function __construct(
        BasketPositionDtoCollectionFactory $basketPositionDtoCollectionFactory,
        ShippingCostCalculator $shippingCostCalculator
    ) {
        $this->basketPositionDtoCollectionFactory = $basketPositionDtoCollectionFactory;
        $this->shippingCostCalculator = $shippingCostCalculator;
    }

    /**
     * @param \oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\BasketDto
     */
    public function create($order)
    {
        $isB2B = boolval($order->getFieldData('oxisnettomode'));

        $grossDeliveryCosts = floatval($order->getFieldData("oxdelcost"));
        $deliveryTax = floatval($order->getFieldData("oxdelvat"));
        $netDeliveryCosts = $this->shippingCostCalculator->calculateNetPrice($grossDeliveryCosts, $deliveryTax);

        // the total monetary value of all applied vouchers
        $totalVoucherDiscount = floatval($order->getFieldData("oxvoucherdiscount"));

        $basket = new BasketDto();
        $basket->currency = strval($order->getFieldData("oxcurrency"));

        $basket->grossTotal = floatval($order->getFieldData("oxtotalordersum"));
        if ($isB2B) {
            $basket->netTotal = floatval($order->getFieldData("oxtotalnetsum")) + $netDeliveryCosts - $totalVoucherDiscount;
        } else {
            $basket->netTotal = floatval($order->getFieldData("oxtotalnetsum")) + $netDeliveryCosts;
        }

        $basket->positions = $this->basketPositionDtoCollectionFactory->create($order);
        return $basket;
    }
}
