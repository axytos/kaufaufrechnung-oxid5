<?php

namespace Axytos\KaufAufRechnung_OXID5\Events;

use Axytos\KaufAufRechnung_OXID5\DependencyInjection\ContainerFactory;
use Axytos\KaufAufRechnung_OXID5\ErrorReporting\ErrorHandler;
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
        self::addOrderCheckProcessStatus();
        self::addOrderPreCheckResult();
        self::addCancelReported();
        self::addCreateInvoiceReported();
        self::addShippingReported();
        self::addReportedTrackingCode();
        self::addOrderBasketHash();
    }

    /**
     * @return void
     */
    private static function addOrderCheckProcessStatus()
    {
        self::addTableColumn(
            "oxorder",
            "AXYTOSKAUFAUFRECHNUNGORDERCHECKPROCESSSTATUS",
            "VARCHAR(128) DEFAULT 'UNCHECKED'"
        );
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
    private static function addCancelReported()
    {
        self::addTableColumn(
            "oxorder",
            "AXYTOSKAUFAUFRECHNUNGCANCELREPORTED",
            "TINYINT(1) NOT NULL DEFAULT 0"
        );
    }

    /**
     * @return void
     */
    private static function addCreateInvoiceReported()
    {
        self::addTableColumn(
            "oxorder",
            "AXYTOSKAUFAUFRECHNUNGCREATEINVOICEREPORTED",
            "TINYINT(1) NOT NULL DEFAULT 0"
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
            "VARCHAR(64) NOT NULL DEFAULT ''"
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

            $languages = oxRegistry::getLang()->getAllShopLanguageIds();

            if (in_array("de", $languages, true)) {
                $lang = strval(array_search("de", $languages, true));
                $payment->setLanguage($lang);
                $payment->oxpayments__oxdesc = new oxField(self::PAYMENT_METHOD_DE_DESC);
                $payment->oxpayments__oxlongdesc = new oxField(self::PAYMENT_METHOD_DE_LONG_DESC);
                $payment->save();
            }

            if (in_array("en", $languages, true)) {
                $lang = strval(array_search("en", $languages, true));
                $payment->setLanguage($lang);
                $payment->oxpayments__oxdesc = new oxField(self::PAYMENT_METHOD_EN_DESC);
                $payment->oxpayments__oxlongdesc = new oxField(self::PAYMENT_METHOD_EN_LONG_DESC);
                $payment->save();
            }
        }

        $metaDataHandler->updateViews();
    }

    /**
     * @return void
     */
    private static function disablePaymentMethod()
    {
        /** @var oxPayment */
        $payment = oxNew(oxPayment::class);
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
