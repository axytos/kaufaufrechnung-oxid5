<?php

namespace Axytos\KaufAufRechnung_OXID5\ValueCalculation;

use oxOrder;

class VoucherDiscountCalculator
{
    /**
     * @param \oxOrder $order
     * @return float
     */
    public function calculate($order)
    {
        // the total monetary value of all applied vouchers
        $voucherDiscountForOrder = floatval($order->getFieldData("oxvoucherdiscount"));

        if ($voucherDiscountForOrder === 0.0) {
            return 0.0;
        }

        return -1 * $voucherDiscountForOrder;
    }
}
