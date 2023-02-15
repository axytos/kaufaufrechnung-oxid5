<?php

namespace Axytos\KaufAufRechnung_OXID5\ValueCalculation;

use oxOrder;

class TrackingIdCalculator
{
    /**
     * @param oxOrder $order
     * @return string[]
     */
    public function calculate($order)
    {
        $trackingCode = strval($order->getFieldData("oxtrackcode"));

        if (!empty($trackingCode)) {
            return [$trackingCode];
        }

        return [];
    }
}
