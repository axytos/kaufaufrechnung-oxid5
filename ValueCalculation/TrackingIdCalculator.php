<?php

namespace Axytos\KaufAufRechnung_OXID5\ValueCalculation;

class TrackingIdCalculator
{
    /**
     * @param \oxOrder $order
     *
     * @return string[]
     */
    public function calculate($order)
    {
        $trackingCode = strval($order->getFieldData('oxtrackcode'));

        if ('' !== $trackingCode) {
            return [$trackingCode];
        }

        return [];
    }
}
