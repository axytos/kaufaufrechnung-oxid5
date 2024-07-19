<?php

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Core\Abstractions\Model\AxytosOrderCheckoutAction;
use Axytos\KaufAufRechnung\Core\Model\AxytosOrderFactory;
use Axytos\KaufAufRechnung_OXID5\Adapter\PluginOrderFactory;
use Axytos\KaufAufRechnung_OXID5\Configuration\PluginConfiguration;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung_OXID5\Events\AxytosEvents;
use Axytos\KaufAufRechnung_OXID5\Extend\AxytosServiceContainer;
use oxRegistry;

class AxytosPaymentGateway extends AxytosPaymentGateway_parent
{
    use AxytosServiceContainer;

    /**
     * @phpstan-ignore-next-line
     * @var \Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler
     */
    private $errorHandler;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\Configuration\PluginConfiguration
     */
    private $pluginConfiguration;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\Adapter\PluginOrderFactory
     */
    private $pluginOrderFactory;
    /**
     * @var \Axytos\KaufAufRechnung\Core\Model\AxytosOrderFactory
     */
    private $axytosOrderFactory;

    public function __construct()
    {
        parent::__construct();
        $this->pluginConfigurationValidator = $this->getFromAxytosServiceContainer(PluginConfigurationValidator::class);
        $this->errorHandler = $this->getFromAxytosServiceContainer(ErrorHandler::class);
        $this->pluginConfiguration = $this->getFromAxytosServiceContainer(PluginConfiguration::class);
        $this->pluginOrderFactory = $this->getFromAxytosServiceContainer(PluginOrderFactory::class);
        $this->axytosOrderFactory = $this->getFromAxytosServiceContainer(AxytosOrderFactory::class);
    }

    /**
     * @return bool
     */
    public function executePayment($amount, &$oOrder)
    {
        /** @var \AxytosOrder */
        $order = $oOrder;
        $session = oxRegistry::getSession();
        $sessionVariableKey = AxytosEvents::PAYMENT_METHOD_ID . '_error_id';
        $sessionVariableErrorMessage = AxytosEvents::PAYMENT_METHOD_ID . '_error_message';

        if (
            is_null($order)
            || is_null($order->getPaymentType())
            || $order->getPaymentType()->getFieldData("oxpaymentsid") !== AxytosEvents::PAYMENT_METHOD_ID
        ) {
            $success = parent::executePayment($amount, $order);
            if ($success) {
                $session->deleteVariable($sessionVariableKey);
                $session->deleteVariable($sessionVariableErrorMessage);
            }
            return $success;
        }

        try {
            /** @var \AxytosOrder */
            $order = $oOrder;

            // add pre-check code here

            $order->initializeOrderNumber();

            $pluginOrder = $this->pluginOrderFactory->create($order);
            $axytosOrder = $this->axytosOrderFactory->create($pluginOrder);
            $axytosOrder->checkout();

            $shopAction = $axytosOrder->getOrderCheckoutAction();

            if ($shopAction === AxytosOrderCheckoutAction::CHANGE_PAYMENT_METHOD) {
                $config = oxRegistry::getConfig();
                $utils = oxRegistry::getUtils();
                $order->delete();

                $session->setVariable($sessionVariableKey, $shopAction);
                $customErrorMessage = $this->pluginConfiguration->getCustomErrorMessage();
                if (!is_null($customErrorMessage)) {
                    $session->setVariable($sessionVariableErrorMessage, $customErrorMessage);
                }

                $utils->redirect($config->getSslShopUrl() . 'index.php?cl=payment&' . AxytosEvents::PAYMENT_METHOD_ID . '_error_id=' . $shopAction, false);
                return false;
            } else {
                $success = parent::executePayment($amount, $order);

                return $success;
            }
        } catch (\Throwable $th) {
            $this->errorHandler->handle($th);
            $order->delete();
            return false;
        } catch (\Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
            $this->errorHandler->handle($th);
            $order->delete();
            return false;
        }
    }
}
