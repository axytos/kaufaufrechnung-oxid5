<?php

namespace Axytos\KaufAufRechnung_OXID5\ValueCalculation;

class ShippingCostCalculator
{
    /**
     * @param float $grossDeliveryCosts
     * @param float $deliveryTax
     * @return float
     */
    public function calculateNetPrice($grossDeliveryCosts, $deliveryTax)
    {
        return round($grossDeliveryCosts / (1 + $deliveryTax / 100), 2);
    }
}
