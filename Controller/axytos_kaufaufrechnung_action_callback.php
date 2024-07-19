<?php

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Core\Abstractions\Model\Actions\ActionExecutorInterface;
use Axytos\KaufAufRechnung\Core\Abstractions\Model\Actions\ActionResultInterface;
use Axytos\KaufAufRechnung\Core\Model\Actions\Results\FatalErrorResult;
use Axytos\KaufAufRechnung\Core\Model\Actions\Results\InvalidDataResult;
use Axytos\KaufAufRechnung\Core\Model\Actions\Results\InvalidMethodResult;
use Axytos\KaufAufRechnung\Core\Model\Actions\Results\PluginNotConfiguredResult;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung_OXID5\Extend\AxytosServiceContainer;
use oxWidget;

/**
 *
 * URL of this controll: http://localhost/widget.php?cl=axytos_kaufaufrechnung_action_callback
 *
 * Widget Controller Development:
 * - For OXID5: use oxWidget instead of WidgetController
 * - For OXID6
 *   - https://docs.oxid-esales.com/developer/en/6.5/development/modules_components_themes/module/skeleton/metadataphp/amodule/controllers.html#controllers
 *   - https://github.com/OXID-eSales/graphql-base-module/blob/b-7.1.x/src/Component/Widget/GraphQL.php
 *   - https://github.com/OXID-eSales/graphql-base-module/blob/b-7.1.x/src/Framework/RequestReader.php
 *   - https://github.com/OXID-eSales/graphql-base-module/blob/b-7.1.x/src/Framework/ResponseWriter.php
 *
 * @package Axytos\KaufAufRechnung_OXID5\Controller
 */
class axytos_kaufaufrechnung_action_callback extends oxWidget
{
    use AxytosServiceContainer;

    /**
     * @var \Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler
     */
    private $errorHandler;
    /**
     * @var \Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator
     */
    private $pluginConfigurationValidator;
    /**
     * @var \Axytos\KaufAufRechnung\Core\Abstractions\Model\Actions\ActionExecutorInterface
     */
    private $actionExecutor;

    /**
     * @var \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface
     */
    private $logger;

    public function __construct()
    {
        parent::__construct();
        $this->errorHandler = $this->getFromAxytosServiceContainer(ErrorHandler::class);
        $this->pluginConfigurationValidator = $this->getFromAxytosServiceContainer(PluginConfigurationValidator::class);
        $this->actionExecutor = $this->getFromAxytosServiceContainer(ActionExecutorInterface::class);
        $this->logger = $this->getFromAxytosServiceContainer(LoggerAdapterInterface::class);
    }

    /**
     * @return void
     */
    public function init()
    {
        try {
            parent::init();

            if ($this->isNotPostRequest()) {
                $this->setResult(new InvalidMethodResult($this->getRequestMethod()));
                return;
            }

            if ($this->pluginConfigurationValidator->isInvalid()) {
                $this->setResult(new PluginNotConfiguredResult());
                return;
            }

            $this->processAction();
        } catch (\Throwable $th) {
            $this->setResult(new FatalErrorResult());
            $this->errorHandler->handle($th);
        } catch (\Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
            $this->setResult(new FatalErrorResult());
            $this->errorHandler->handle($th);
        }
    }

    /**
     * @return void
     */
    private function processAction()
    {
        $rawBody = $this->getRequestBody();

        if ($rawBody === '') {
            $this->logger->error('Process Action Request: HTTP request body empty');
            $this->setResult(new InvalidDataResult('HTTP request body empty'));
            return;
        }

        $decodedBody = json_decode($rawBody, true);
        if (!is_array($decodedBody)) {
            $this->logger->error('Process Action Request: HTTP request body is not a json object');
            $this->setResult(new InvalidDataResult('HTTP request body is not a json object'));
            return;
        }

        $loggableRequestBody = $decodedBody;
        if (array_key_exists('clientSecret', $loggableRequestBody)) {
            $loggableRequestBody['clientSecret'] = '****';
        }
        $encodedLoggableRequestBody = json_encode($loggableRequestBody);
        $this->logger->info("Process Action Request: request body '$encodedLoggableRequestBody'");

        $clientSecret = array_key_exists('clientSecret', $decodedBody) ? $decodedBody['clientSecret'] : null;
        if (!is_string($clientSecret)) {
            $this->logger->error("Process Action Request: Required string property 'clientSecret' is missing");
            $this->setResult(new InvalidDataResult('Required string property', 'clientSecret'));
            return;
        }

        $action = array_key_exists('action', $decodedBody) ?  $decodedBody['action'] : null;
        if (!is_string($action)) {
            $this->logger->error("Process Action Request: Required string property 'action' is missing");
            $this->setResult(new InvalidDataResult('Required string property', 'action'));
            return;
        }

        $params = array_key_exists('params', $decodedBody) ? $decodedBody['params'] : null;
        if (!is_null($params) && !is_array($params)) {
            $this->logger->error("Process Action Request: Optional object property 'params' ist not an array");
            $this->setResult(new InvalidDataResult('Optional object property', 'params'));
            return;
        }

        $result = $this->actionExecutor->executeAction($clientSecret, $action, $params);
        $this->setResult($result);
    }

    /**
     * @return string
     */
    private function getRequestBody()
    {
        // see:
        // - https://github.com/OXID-eSales/graphql-base-module/blob/b-7.1.x/src/Framework/RequestReader.php#L79
        // - https://stackoverflow.com/a/8945912

        $rawBody = file_get_contents('php://input');
        if (!is_string($rawBody)) {
            return '';
        }
        return $rawBody;
    }

    /**
     * @return string
     */
    private function getRequestMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * @return bool
     */
    private function isNotPostRequest()
    {
        return $this->getRequestMethod() !== 'POST';
    }

    /**
     * @param \Axytos\KaufAufRechnung\Core\Abstractions\Model\Actions\ActionResultInterface $result
     * @return void
     */
    private function setResult($result)
    {
        $this->sendJsonResponse($result->getHttpStatusCode(), $result);
    }

    /**
     * @param int $statusCode
     * @param mixed $data
     * @return never
     */
    private function sendJsonResponse($statusCode, $data)
    {
        // see: https://github.com/OXID-eSales/graphql-base-module/blob/b-7.1.x/src/Framework/ResponseWriter.php
        // use exit for full stop of php request processing and prevent oxid from rendering maintenance mode

        header('Content-Type: application/json', true, $statusCode);
        exit(json_encode($data));
    }
}
