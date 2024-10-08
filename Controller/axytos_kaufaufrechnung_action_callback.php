<?php

use Axytos\ECommerce\Clients\Invoice\PluginConfigurationValidator;
use Axytos\KaufAufRechnung\Core\Abstractions\Model\Actions\ActionExecutorInterface;
use Axytos\KaufAufRechnung\Core\AxytosActionControllerTrait;
use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Logging\LoggerAdapterInterface;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
use Axytos\KaufAufRechnung_OXID5\Extend\AxytosServiceContainer;
use oxWidget;

/**
 * URL of this controll: http://localhost/widget.php?cl=axytos_kaufaufrechnung_action_callback.
 *
 * Widget Controller Development:
 * - For OXID5: use oxWidget instead of WidgetController
 * - For OXID6
 *   - https://docs.oxid-esales.com/developer/en/6.5/development/modules_components_themes/module/skeleton/metadataphp/amodule/controllers.html#controllers
 *   - https://github.com/OXID-eSales/graphql-base-module/blob/b-7.1.x/src/Component/Widget/GraphQL.php
 *   - https://github.com/OXID-eSales/graphql-base-module/blob/b-7.1.x/src/Framework/RequestReader.php
 *   - https://github.com/OXID-eSales/graphql-base-module/blob/b-7.1.x/src/Framework/ResponseWriter.php
 */
class axytos_kaufaufrechnung_action_callback extends oxWidget
{
    use AxytosServiceContainer;
    use AxytosActionControllerTrait;

    /**
     * @var ErrorHandler
     */
    private $errorHandler;

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
            $this->executeActionInternal();
        } catch (Throwable $th) {
            $this->setErrorResult();
            $this->errorHandler->handle($th);
        } catch (Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
            $this->setErrorResult();
            $this->errorHandler->handle($th);
        }
    }

    /**
     * @return string
     */
    protected function getRequestBody()
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
    protected function getRequestMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    /**
     * @param string $responseBody
     * @param int    $statusCode
     *
     * @return void
     */
    protected function setResponseBody($responseBody, $statusCode)
    {
        $this->sendJsonResponse($statusCode, $responseBody);
    }

    /**
     * @param int    $statusCode
     * @param string $data
     *
     * @return never
     */
    private function sendJsonResponse($statusCode, $data)
    {
        // see: https://github.com/OXID-eSales/graphql-base-module/blob/b-7.1.x/src/Framework/ResponseWriter.php
        // use exit for full stop of php request processing and prevent oxid from rendering maintenance mode

        header('Content-Type: application/json', true, $statusCode);
        exit($data);
    }
}
