<?php

namespace Axytos\KaufAufRechnung_OXID5\Events;

use Axytos\KaufAufRechnung_OXID5\DependencyInjection\ContainerFactory;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
use OxidEsales\Eshop\Application\Model\Payment;
use oxDb;
use oxDbMetaDataHandler;
use oxField;
use oxPayment;
use oxRegistry;
use Throwable;

class AxytosEvents
{
    const PAYMENT_METHOD_ID = "axytos_kaufaufrechnung";
    const PAYMENT_METHOD_DE_DESC = "Kauf auf Rechnung";
    const PAYMENT_METHOD_DE_LONG_DESC = "Sie zahlen bequem die Rechnung, sobald Sie die Ware erhalten haben, innerhalb der Zahlfrist";
    const PAYMENT_METHOD_EN_DESC = "Buy Now Pay Later";
    const PAYMENT_METHOD_EN_LONG_DESC = "You conveniently pay the invoice as soon as you receive the goods, within the payment period";
    const PAYMENT_METHOD_FR_DESC = "Buy Now Pay Later";
    const PAYMENT_METHOD_FR_LONG_DESC = "Vous payez la facture dès que vous recevez la marchandise, dans le délai de paiement.";
    const PAYMENT_METHOD_NL_DESC = "Buy Now Pay Later";
    const PAYMENT_METHOD_NL_LONG_DESC = "Je moet de factuur betalen zodra je de goederen hebt ontvangen, binnen de betalingstermijn.";
    const PAYMENT_METHOD_ES_DESC = "Buy Now Pay Later";
    const PAYMENT_METHOD_ES_LONG_DESC = "Pagas la factura convenientemente en cuanto has recibido la mercancía, dentro del plazo de pago.";


    public function __construct()
    {
    }

    /**
     * @return void
     */
    public static function onActivate()
    {
        try {
            self::createOrderColumns();
            self::addPaymentMethod();
            self::clearTmp();
        } catch (\Throwable $th) {
            self::handleError($th);
        } catch (\Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
            self::handleError($th);
        }
    }

    /**
     * @return void
     */
    public static function onDeactivate()
    {
        try {
            self::disablePaymentMethod();
            self::clearTmp();
        } catch (\Throwable $th) {
            self::handleError($th);
        } catch (\Exception $th) { // @phpstan-ignore-line | php5.6 compatibility
            self::handleError($th);
        }
    }

    /**
     * @return void
     */
    private static function createOrderColumns()
    {
        self::addOrderPreCheckResult();
        self::addShippingReported();
        self::addReportedTrackingCode();
        self::addOrderBasketHash();
        self::addOrderState();
        self::addOrderStateData();
    }

    /**
     * @return void
     */
    private static function addOrderPreCheckResult()
    {
        self::addTableColumn(
            "oxorder",
            "AXYTOSKAUFAUFRECHNUNGORDERPRECHECKRESULT",
            "TEXT"
        );
    }

    /**
     * @return void
     */
    private static function addShippingReported()
    {
        self::addTableColumn(
            "oxorder",
            "AXYTOSKAUFAUFRECHNUNGSHIPPINGREPORTED",
            "TINYINT(1) NOT NULL DEFAULT 0"
        );
    }

    /**
     * @return void
     */
    private static function addReportedTrackingCode()
    {
        self::addTableColumn(
            "oxorder",
            "AXYTOSKAUFAUFRECHNUNGREPORTEDTRACKINGCODE",
            "VARCHAR(128) NOT NULL DEFAULT ''"
        );
    }

    /**
     * @return void
     */
    private static function addOrderBasketHash()
    {
        self::addTableColumn(
            "oxorder",
            "AXYTOSKAUFAUFRECHNUNGORDERBASKETHASH",
            "VARCHAR(64) NOT NULL DEFAULT ''" // possible hash sha256 with 64 chars, but not sha512!
        );
    }

    /**
     * @return void
     */
    private static function addOrderState()
    {
        self::addTableColumn(
            "oxorder",
            "AXYTOSKAUFAUFRECHNUNGORDERSTATE",
            "TEXT"
        );
    }

    /**
     * @return void
     */
    private static function addOrderStateData()
    {
        self::addTableColumn(
            "oxorder",
            "AXYTOSKAUFAUFRECHNUNGORDERSTATEDATA",
            "TEXT"
        );
    }

    /**
     * @return void
     * @param string $tableName
     * @param string $columnName
     * @param string $definition
     */
    private static function addTableColumn($tableName, $columnName, $definition)
    {
        $database = oxDb::getDb();
        $row = $database->getRow(
            "SELECT COLUMN_NAME " .
                "FROM INFORMATION_SCHEMA.COLUMNS " .
                "WHERE TABLE_SCHEMA = DATABASE() " .
                "AND TABLE_NAME = ? " .
                "AND COLUMN_NAME = ?",
            [$tableName, $columnName]
        );

        if ($row !== []) {
            return;
        }

        $database->execute("ALTER TABLE $tableName ADD COLUMN $columnName $definition");
    }


    /**
     * @return void
     */
    private static function addPaymentMethod()
    {
        /** @var oxDbMetaDataHandler */
        $metaDataHandler = oxNew(oxDbMetaDataHandler::class);

        /** @var oxPayment */
        $payment = oxNew(oxPayment::class);
        if ($payment->load(self::PAYMENT_METHOD_ID)) {
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxactive = new oxField(1);
            $payment->save();
        } else {
            $payment->setId(self::PAYMENT_METHOD_ID);

            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxdesc = new oxField(self::PAYMENT_METHOD_DE_DESC);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxlongdesc = new oxField(self::PAYMENT_METHOD_DE_LONG_DESC);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxactive = new oxField(1);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxfromamount = new oxField(0);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxtoamount = new oxField(1000000);
            $payment->save();
        }

        $languages = oxRegistry::getLang()->getAllShopLanguageIds();

        if (in_array("de", $languages, true)) {
            $lang = strval(array_search("de", $languages, true));
            $payment->setLanguage($lang);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxdesc = new oxField(self::PAYMENT_METHOD_DE_DESC);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxlongdesc = new oxField(self::PAYMENT_METHOD_DE_LONG_DESC);
            $payment->save();
        }

        if (in_array("en", $languages, true)) {
            $lang = strval(array_search("en", $languages, true));
            $payment->setLanguage($lang);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxdesc = new oxField(self::PAYMENT_METHOD_EN_DESC);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxlongdesc = new oxField(self::PAYMENT_METHOD_EN_LONG_DESC);
            $payment->save();
        }

        if (in_array("fr", $languages, true)) {
            $lang = strval(array_search("fr", $languages, true));
            $payment->setLanguage($lang);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxdesc = new oxField(self::PAYMENT_METHOD_FR_DESC);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxlongdesc = new oxField(self::PAYMENT_METHOD_FR_LONG_DESC);
            $payment->save();
        }

        if (in_array("nl", $languages, true)) {
            $lang = strval(array_search("nl", $languages, true));
            $payment->setLanguage($lang);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxdesc = new oxField(self::PAYMENT_METHOD_NL_DESC);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxlongdesc = new oxField(self::PAYMENT_METHOD_NL_LONG_DESC);
            $payment->save();
        }

        if (in_array("es", $languages, true)) {
            $lang = strval(array_search("es", $languages, true));
            $payment->setLanguage($lang);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxdesc = new oxField(self::PAYMENT_METHOD_ES_DESC);
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxlongdesc = new oxField(self::PAYMENT_METHOD_ES_LONG_DESC);
            $payment->save();
        }

        $metaDataHandler->updateViews();
    }

    /**
     * @return void
     */
    private static function disablePaymentMethod()
    {
        /** @var Payment */
        $payment = oxNew(Payment::class);
        if ($payment->load(self::PAYMENT_METHOD_ID)) {
            /** @phpstan-ignore-next-line */
            $payment->oxpayments__oxactive = new oxField(0);

            $payment->save();
        }
    }

    /**
     * @return void
     */
    private static function clearTmp()
    {
        $sTmpDir = getShopBasePath() . "/tmp/";
        $sSmartyDir = $sTmpDir . "smarty/";

        /** @phpstan-ignore-next-line */
        foreach (glob($sTmpDir . "*.txt") as $sFileName) {
            unlink($sFileName);
        }
        /** @phpstan-ignore-next-line */
        foreach (glob($sSmartyDir . "*.php") as $sFileName) {
            unlink($sFileName);
        }
    }

    /**
     * @return void
     * @param \Throwable $error
     */
    private static function handleError($error)
    {
        $container = ContainerFactory::getInstance()->getContainer();
        /** @var ErrorHandler */
        $errorHandler = $container->get(ErrorHandler::class);
        $errorHandler->handle($error);
    }
}
