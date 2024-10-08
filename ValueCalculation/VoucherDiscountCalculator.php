<?php

namespace Axytos\KaufAufRechnung_OXID5\ValueCalculation;

class VoucherDiscountCalculator
{
    /**
     * @param \oxOrder $order
     *
     * @return float
     */
    public function calculate($order)
    {
        // the total monetary value of all applied vouchers
        $voucherDiscountForOrder = floatval($order->getFieldData('oxvoucherdiscount'));

        if (0.0 === $voucherDiscountForOrder) {
            return 0.0;
        }

        return -1 * $voucherDiscountForOrder;
    }
}
