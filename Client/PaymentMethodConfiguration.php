<?php

namespace Axytos\KaufAufRechnung_OXID5\Client;

use Axytos\ECommerce\Abstractions\PaymentMethodConfigurationInterface;

class PaymentMethodConfiguration implements PaymentMethodConfigurationInterface
{
    /**
     * @param string $paymentMethodId
     * @return bool
     */
    public function isIgnored($paymentMethodId)
    {
        return false;
    }

    /**
     * @param string $paymentMethodId
     * @return bool
     */
    public function isSafe($paymentMethodId)
    {
        return false;
    }

    /**
     * @param string $paymentMethodId
     * @return bool
     */
    public function isUnsafe($paymentMethodId)
    {
        return false;
    }

    /**
     * @param string $paymentMethodId
     * @return bool
     */
    public function isNotConfigured($paymentMethodId)
    {
        return true;
    }
}
