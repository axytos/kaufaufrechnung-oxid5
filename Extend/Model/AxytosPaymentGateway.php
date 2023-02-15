<?php

use Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface;
use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\ECommerce\Clients\Invoice\ShopActions;
use Axytos\ECommerce\OrderSync\OrderHashCalculator;
use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContextFactory;
use Axytos\KaufAufRechnung_OXID5\Core\OrderCheckProcessStateMachine;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung_OXID5\Events\AxytosEvents;
use Axytos\KaufAufRechnung_OXID5\Extend\ServiceContainer;
use oxRegistry;

class AxytosPaymentGateway extends AxytosPaymentGateway_parent
{
    use ServiceContainer;

    /** @phpstan-ignore-next-line
     * @var \Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator */
    private $pluginConfigurationValidator;
    /** @phpstan-ignore-next-line
     * @var \Axytos\ECommerce\Clients\Invoice\InvoiceClientInterface */
    private $invoiceClient;
    /** @phpstan-ignore-next-line
     * @var \Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler */
    private $errorHandler;
    /** @phpstan-ignore-next-line
     * @var \Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContextFactory */
    private $invoiceOrderContextFactory;
    /** @phpstan-ignore-next-line
     * @var \Axytos\KaufAufRechnung_OXID5\Core\OrderCheckProcessStateMachine */
    private $orderCheckProcessStateMachine;
    /**
     * @var \Axytos\ECommerce\OrderSync\OrderHashCalculator
     */
    private $orderHashCalculator;

    public function __construct()
    {
        $this->pluginConfigurationValidator = $this->getServiceFromContainer(PluginConfigurationValidator::class);
        $this->invoiceClient = $this->getServiceFromContainer(InvoiceClientInterface::class);
        $this->errorHandler = $this->getServiceFromContainer(ErrorHandler::class);
        $this->invoiceOrderContextFactory = $this->getServiceFromContainer(InvoiceOrderContextFactory::class);
        $this->orderCheckProcessStateMachine = $this->getServiceFromContainer(OrderCheckProcessStateMachine::class);
        $this->orderHashCalculator = $this->getServiceFromContainer(OrderHashCalculator::class);
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

        if ($order->getPaymentType()->getFieldData("oxpaymentsid") !== AxytosEvents::PAYMENT_METHOD_ID) {
            $success = parent::executePayment($amount, $order);
            if ($success) {
                $session->deleteVariable($sessionVariableKey);
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
                $this->orderCheckProcessStateMachine->setFailed($order);
                $order->delete();
                $session->setVariable($sessionVariableKey, $shopAction);
                $utils->redirect($config->getSslShopUrl() . 'index.php?cl=payment&' . AxytosEvents::PAYMENT_METHOD_ID . '_error_id=' . ShopActions::CHANGE_PAYMENT_METHOD, false);
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
