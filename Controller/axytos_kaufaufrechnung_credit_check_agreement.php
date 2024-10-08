<?php

use Axytos\ECommerce\Clients\Checkout\CheckoutClientInterface;
use Axytos\KaufAufRechnung_OXID5\DependencyInjection\ContainerFactory;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
use oxUBase;

class axytos_kaufaufrechnung_credit_check_agreement extends oxUBase
{
    protected $_sThisTemplate = 'credit_check_agreement.tpl';

    /**
     * @return string
     */
    public function getCreditCheckAgreement()
    {
        try {
            /** @var CheckoutClientInterface */
            $checkoutClient = ContainerFactory::getInstance()
                ->getContainer()
                ->get(CheckoutClientInterface::class)
            ;

            return $checkoutClient->getCreditCheckAgreementInfo();
        } catch (Throwable $th) {
            /** @var ErrorHandler */
            $errorHandler = ContainerFactory::getInstance()
                ->getContainer()
                ->get(ErrorHandler::class)
            ;
            $errorHandler->handle($th);

            return '';
        } catch (Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
            /** @var ErrorHandler */
            $errorHandler = ContainerFactory::getInstance()
                ->getContainer()
                ->get(ErrorHandler::class)
            ;
            $errorHandler->handle($th);

            return '';
        }
    }
}
