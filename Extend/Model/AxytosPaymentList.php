<?php

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung_OXID5\Events\AxytosEvents;
use Axytos\KaufAufRechnung_OXID5\Extend\AxytosServiceContainer;

class AxytosPaymentList extends AxytosPaymentList_parent
{
    use AxytosServiceContainer;

    /**
     * @param oxUser $oUser      — session user object
     * @param string $sShipSetId
     * @param float  $dPrice
     *
     * @return array<\oxPayment>
     */
    public function getPaymentList($sShipSetId, $dPrice, $oUser = null)
    {
        try {
            /** @var array<\oxPayment> */
            $paymentList = parent::getPaymentList($sShipSetId, $dPrice, $oUser);

            $pluginConfigurationValidator = $this->getFromAxytosServiceContainer(PluginConfigurationValidator::class);
            if ($pluginConfigurationValidator->isInvalid()) {
                unset($paymentList[AxytosEvents::PAYMENT_METHOD_ID]);
            }

            return $paymentList;
        } catch (Throwable $th) {
            /** @var ErrorHandler */
            $errorHandler = $this->getFromAxytosServiceContainer(ErrorHandler::class);
            $errorHandler->handle($th);

            try {
                // retry, error might not originate from parent
                return parent::getPaymentList($sShipSetId, $dPrice, $oUser);
            } catch (Throwable $th) {
                return [];
            } catch (Exception $th) { // @phpstan-ignore-line bcause of php 5.6 compatibility
                return [];
            }
        } catch (Exception $th) { // @phpstan-ignore-line bcause of php 5.6 compatibility
            /** @var ErrorHandler */
            $errorHandler = $this->getFromAxytosServiceContainer(ErrorHandler::class);
            $errorHandler->handle($th);
            try {
                // retry, error might not originate from parent
                return parent::getPaymentList($sShipSetId, $dPrice, $oUser);
            } catch (Throwable $th) {
                return [];
            } catch (Exception $th) {
                return [];
            }
        }
    }
}
