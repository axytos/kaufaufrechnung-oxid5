<?php

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\ECommerce\Clients\Invoice\ShopActions;
use Axytos\ECommerce\OrderSync\OrderHashCalculator;
use Axytos\KaufAufRechnung_OXID5\Configuration\PluginConfiguration;
use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContextFactory;
use Axytos\KaufAufRechnung_OXID5\Core\OrderCheckProcessStateMachine;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung_OXID5\Events\AxytosEvents;
use Axytos\KaufAufRechnung_OXID5\Extend\AxytosServiceContainer;
use oxRegistry;

class AxytosPaymentGateway extends AxytosPaymentGateway_parent
{
    use AxytosServiceContainer;

    /** @phpstan-ignore-next-line
     * @var \Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator */
    private $pluginConfigurationValidator;
    /**
     * @var \Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface
     */
    private $invoiceClient;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler
     */
    private $errorHandler;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContextFactory
     */
    private $invoiceOrderContextFactory;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\Core\OrderCheckProcessStateMachine
     */
    private $orderCheckProcessStateMachine;
    /**
     * @var \Axytos\ECommerce\OrderSync\OrderHashCalculator
     */
    private $orderHashCalculator;
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\Configuration\PluginConfiguration
     */
    private $pluginConfiguration;

    public function __construct()
    {
        parent::__construct();
        $this->pluginConfigurationValidator = $this->getFromAxytosServiceContainer(PluginConfigurationValidator::class);
        $this->invoiceClient = $this->getFromAxytosServiceContainer(InvoiceClientInterface::class);
        $this->errorHandler = $this->getFromAxytosServiceContainer(ErrorHandler::class);
        $this->invoiceOrderContextFactory = $this->getFromAxytosServiceContainer(InvoiceOrderContextFactory::class);
        $this->orderCheckProcessStateMachine = $this->getFromAxytosServiceContainer(OrderCheckProcessStateMachine::class);
        $this->orderHashCalculator = $this->getFromAxytosServiceContainer(OrderHashCalculator::class);
        $this->pluginConfiguration = $this->getFromAxytosServiceContainer(PluginConfiguration::class);
    }

    /**
     * @return bool
     */
    public function executePayment($amount, &$oOrder)
    {
        /** @var AxytosOrder */
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
            /** @var AxytosOrder */
            $order = $oOrder;

            // add pre-check code here
            $invoiceOrderContext = $this->invoiceOrderContextFactory->getInvoiceOrderContext($order);
            $orderBasketHash = $this->orderHashCalculator->computeBasketHash($invoiceOrderContext);

            $shopAction = $this->invoiceClient->precheck($invoiceOrderContext);

            if ($shopAction === ShopActions::CHANGE_PAYMENT_METHOD) {
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
                $order->initializeOrderNumber();

                $this->orderCheckProcessStateMachine->setChecked($order, $orderBasketHash);

                $this->invoiceClient->confirmOrder($invoiceOrderContext);

                $this->orderCheckProcessStateMachine->setConfirmed($order);

                $success = parent::executePayment($amount, $order);

                return $success;
            }
        } catch (\Throwable $th) {
            $this->orderCheckProcessStateMachine->setFailed($order);
            $this->errorHandler->handle($th);
            $order->delete();
            return false;
        } catch (\Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
            $this->orderCheckProcessStateMachine->setFailed($order);
            $this->errorHandler->handle($th);
            $order->delete();
            return false;
        }
    }
}
