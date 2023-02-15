<?php

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung_OXID5\Events\AxytosEvents;
use Axytos\KaufAufRechnung_OXID5\Extend\ServiceContainer;
use oxPayment;
use oxUser;

class AxytosPaymentList extends AxytosPaymentList_parent
{
    use ServiceContainer;

    /**
     * @param oxUser $oUser — session user object
     * @return array<oxPayment>
     */
    public function getPaymentList($sShipSetId, $dPrice, $oUser = null)
    {
        try {
            /** @var array<oxPayment> */
            $paymentList = parent::getPaymentList($sShipSetId, $dPrice, $oUser);

            $pluginConfigurationValidator = $this->getServiceFromContainer(PluginConfigurationValidator::class);
            if ($pluginConfigurationValidator->isInvalid()) {
                unset($paymentList[AxytosEvents::PAYMENT_METHOD_ID]);
            }

            return $paymentList;
        } catch (\Throwable $th) {
            /** @var ErrorHandler */
            $errorHandler = $this->getServiceFromContainer(ErrorHandler::class);
            $errorHandler->handle($th);

            try {
                // retry, error might not originate from parent
                return parent::getPaymentList($sShipSetId, $dPrice, $oUser);
            } catch (\Throwable $th) {
                return [];
            } catch (\Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
                return [];
            }
        } catch (\Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
            /** @var ErrorHandler */
            $errorHandler = $this->getServiceFromContainer(ErrorHandler::class);
            $errorHandler->handle($th);
            try {
                // retry, error might not originate from parent
                return parent::getPaymentList($sShipSetId, $dPrice, $oUser);
            } catch (\Throwable $th) {
                return [];
            } catch (\Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
                return [];
            }
        }
    }
}
