<?php

namespace Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer;

use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\KaufAufRechnung\Core\Model\OrderStateMachine\OrderStates;
use Axytos\KaufAufRechnung_OXID5\Events\AxytosEvents;

class OrderRepository
{
    /**
     * @return void
     */
    public function startTransaction()
    {
        \oxDb::getDb()->startTransaction();
    }

    /**
     * @return void
     */
    public function commitTransaction()
    {
        \oxDb::getDb()->commitTransaction();
    }

    /**
     * @return void
     */
    public function rollbackTransaction()
    {
        \oxDb::getDb()->rollbackTransaction();
    }

    /**
     * @param string $orderId
     *
     * @return \oxOrder|null
     */
    public function findOrder($orderId)
    {
        /** @var \oxOrder */
        $order = oxNew('oxorder');
        if ($order->load($orderId)) {
            return $order;
        }

        return null;
    }

    /**
     * @return \oxOrder[]
     */
    public function findAllToSync()
    {
        $filters = [
            'axytoskaufaufrechnungcancelreported = 0 AND oxstorno = 1',
            "axytoskaufaufrechnungcreateinvoicereported = 0 AND oxbillnr IS NOT NULL AND oxbillnr != ''",
            "axytoskaufaufrechnungshippingreported = 0 AND oxsenddate != '' AND oxsenddate != '-' AND oxsenddate != '0000-00-00 00:00:00'",
            'axytoskaufaufrechnungreportedtrackingcode != oxtrackcode',
        ];
        $query = 'SELECT oxid FROM oxorder ' .
            'WHERE oxpaymenttype = ? AND axytoskaufaufrechnungordercheckprocessstatus = ? AND ' .
            '((' . implode(') OR (', $filters) . '))';

        $orderIds = \oxDb::getDb()->getCol($query, [
            AxytosEvents::PAYMENT_METHOD_ID,
            OrderCheckProcessStates::CONFIRMED,
        ]);

        $orders = array_map([$this, 'findOrder'], $orderIds);

        return array_filter($orders);
    }

    /**
     * @return \oxOrder[]
     */
    public function findAllToUpdate()
    {
        $query = 'SELECT oxid FROM oxorder ' .
            'WHERE oxpaymenttype = ? ' .
            'AND axytoskaufaufrechnungordercheckprocessstatus = ? ' .
            'AND axytoskaufaufrechnungcreateinvoicereported = ?';

        $orderIds = \oxDb::getDb()->getCol($query, [
            AxytosEvents::PAYMENT_METHOD_ID,
            OrderCheckProcessStates::CONFIRMED,
            0,
        ]);

        $orders = array_map([$this, 'findOrder'], $orderIds);

        return array_filter($orders);
    }

    /**
     * @param \oxOrder $order
     *
     * @return string
     */
    public function findLogistician($order)
    {
        $db = \oxDb::getDb();
        $value = $db->getOne(
            'SELECT oxtitle FROM oxdeliveryset WHERE oxid = ?',
            [$order->getFieldData('oxdeltype')]
        );

        return '' !== strval($value) ? strval($value) : '';
    }

    /**
     * @param mixed $countryId
     *
     * @return string|null
     */
    public function findDeliveryAddressCountryById($countryId)
    {
        $db = \oxDb::getDb();
        $country = $db->getOne(
            'SELECT oxcountry.oxisoalpha2 FROM oxcountry WHERE oxid = ?',
            [$countryId]
        );

        $country = strval($country);

        return '' !== $country ? $country : null;
    }

    /**
     * @param mixed $stateId
     *
     * @return string|null
     */
    public function findDeliveryAddressStateById($stateId)
    {
        $db = \oxDb::getDb();
        $state = $db->getOne(
            'SELECT oxstates.oxtitle FROM oxstates WHERE oxid = ?',
            [$stateId]
        );

        $state = strval($state);

        return '' !== $state ? $state : null;
    }

    /**
     * @param mixed $countryId
     *
     * @return string|null
     */
    public function findInvoiceAddressCountryById($countryId)
    {
        $db = \oxDb::getDb();
        $country = $db->getOne(
            'SELECT oxcountry.oxisoalpha2 FROM oxcountry WHERE oxid = ?',
            [$countryId]
        );

        $country = strval($country);

        return '' !== $country ? $country : null;
    }

    /**
     * @param mixed $stateId
     *
     * @return string|null
     */
    public function findInvoiceAddressStateById($stateId)
    {
        $db = \oxDb::getDb();
        $state = $db->getOne(
            'SELECT oxstates.oxtitle FROM oxstates WHERE oxid = ?',
            [$stateId]
        );

        $state = strval($state);

        return '' !== $state ? $state : null;
    }

    /**
     * @param string[]    $orderStates
     * @param int|null    $limit
     * @param string|null $startId
     *
     * @return \oxOrder[]
     */
    public function getOrdersByStates($orderStates, $limit = null, $startId = null)
    {
        if (0 === count($orderStates)) {
            return [];
        }

        $orderStates = array_values($orderStates);

        $orderStatePlaceholders = join(',', array_fill(0, count($orderStates), '?'));

        $parameters = [
            AxytosEvents::PAYMENT_METHOD_ID,
        ];

        if (!is_null($startId)) {
            $parameters[] = intval($startId);
        } else {
            $parameters[] = 0;
        }

        foreach ($orderStates as $orderState) {
            array_push($parameters, $orderState);
        }

        $sql = "SELECT oxorder.oxid 
        FROM oxorder 
        WHERE oxpaymenttype = ? 
        AND oxordernr >= ?
        AND (axytoskaufaufrechnungorderstate IS NULL OR axytoskaufaufrechnungorderstate IN ({$orderStatePlaceholders}))
        ORDER BY oxordernr";

        if (!is_null($limit)) {
            $sql .= ' LIMIT ?';
            array_push($parameters, $limit);
        }

        $db = \oxDb::getDb(\oxDb::FETCH_MODE_ASSOC);
        $rows = $db->getAll($sql, $parameters);

        /** @var array<string> */
        $orderIds = array_map(function ($row) {
            return $row['oxid'];
        }, $rows);

        $orders = array_map([$this, 'findOrder'], $orderIds);

        return array_filter($orders);
    }

    /**
     * @param string|int $orderNumber
     *
     * @return \oxOrder|null
     */
    public function getOrderByOrderNumber($orderNumber)
    {
        $orderNumber = intval($orderNumber);

        $parameters = [
            AxytosEvents::PAYMENT_METHOD_ID,
            $orderNumber,
        ];

        $sql = 'SELECT oxorder.oxid 
        FROM oxorder 
        WHERE oxpaymenttype = ? AND oxordernr = ?';

        $db = \oxDb::getDb();
        $oxid = strval($db->getOne($sql, $parameters));

        return $this->findOrder($oxid);
    }

    /**
     * @return void
     */
    public function migrateOrderStates()
    {
        $db = \oxDb::getDb();

        // // SQL to check if column exists
        $checkColumnSql = 'SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?';

        $tableName = 'oxorder';
        $orderCheckProcessStatusColumnName = 'axytoskaufaufrechnungordercheckprocessstatus';
        $hasCancelReportedColumnName = 'axytoskaufaufrechnungcancelreported';
        $hasCreateInvoiceColumnName = 'axytoskaufaufrechnungcreateinvoicereported';
        $orderStateColumnName = 'axytoskaufaufrechnungorderstate';

        $checkProcessStatusColumnExists = intval($db->getOne($checkColumnSql, [$tableName, $orderCheckProcessStatusColumnName]));
        $hasCancelReportedColumnExists = intval($db->getOne($checkColumnSql, [$tableName, $hasCancelReportedColumnName]));
        $hasCreateInvoiceColumnExists = intval($db->getOne($checkColumnSql, [$tableName, $hasCreateInvoiceColumnName]));
        $orderStateColumnExists = intval($db->getOne($checkColumnSql, [$tableName, $orderStateColumnName]));

        if (
            1 === $checkProcessStatusColumnExists
            && 1 === $hasCancelReportedColumnExists
            && 1 === $hasCreateInvoiceColumnExists
            && 1 === $orderStateColumnExists
        ) {
            $sql = 'SELECT oxorder.oxid 
            FROM oxorder 
            WHERE axytoskaufaufrechnungorderstate IS NULL AND oxpaymenttype = ?';

            $parameters = [
                AxytosEvents::PAYMENT_METHOD_ID,
            ];

            $db = \oxDb::getDb(\oxDb::FETCH_MODE_ASSOC);
            $rows = $db->getAll($sql, $parameters);

            /** @var array<string> */
            $orderIds = array_map(function ($row) {
                return $row['oxid'];
            }, $rows);

            foreach ($orderIds as $orderId) {
                /** @var \oxOrder */
                $order = $this->findOrder($orderId);
                $orderState = $this->mapAttributesToOrderState($order);
                // do not overwrite existing values with null
                if (!is_null($orderState)) {
                    /** @phpstan-ignore-next-line */
                    $order->oxorder__axytoskaufaufrechnungorderstate = new \oxField($orderState);
                    $order->save();
                }
            }
        }
    }

    /**
     * @param \oxOrder|null $order
     *
     * @return string|null
     */
    private function mapAttributesToOrderState($order)
    {
        if (is_null($order)) {
            return null;
        }

        $checkProcessState = strval($order->getFieldData('axytoskaufaufrechnungordercheckprocessstatus'));
        $hasCancelReported = boolval($order->getFieldData('axytoskaufaufrechnungcancelreported'));
        $hasCreateInvoiceReported = boolval($order->getFieldData('axytoskaufaufrechnungcreateinvoicereported'));
        $hasRefundReported = false; // refund reports are currently not a supported feature for oxid
        $hasPaymentReproted = false; // payment reports are currently not a supported feature for oxid

        switch ($checkProcessState) {
            case OrderCheckProcessStates::CHECKED:
            case OrderCheckProcessStates::FAILED:
                return OrderStates::CHECKOUT_FAILED;
            case OrderCheckProcessStates::CONFIRMED:
                if ($hasPaymentReproted) {  /** @phpstan-ignore-line */
                    return OrderStates::COMPLETELY_PAID;
                }
                if ($hasRefundReported) {  /** @phpstan-ignore-line */
                    return OrderStates::COMPLETELY_REFUNDED;
                }
                if ($hasCreateInvoiceReported) {
                    return OrderStates::INVOICED;
                }
                if ($hasCancelReported) {
                    return OrderStates::CANCELED;
                }

                return OrderStates::CHECKOUT_CONFIRMED;

            case OrderCheckProcessStates::UNCHECKED:
            default:
                return null;
        }
    }

    /**
     * @return void
     */
    public static function createOrderColumns()
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
            'oxorder',
            'AXYTOSKAUFAUFRECHNUNGORDERPRECHECKRESULT',
            'TEXT'
        );
    }

    /**
     * @return void
     */
    private static function addShippingReported()
    {
        self::addTableColumn(
            'oxorder',
            'AXYTOSKAUFAUFRECHNUNGSHIPPINGREPORTED',
            'TINYINT(1) NOT NULL DEFAULT 0'
        );
    }

    /**
     * @return void
     */
    private static function addReportedTrackingCode()
    {
        self::addTableColumn(
            'oxorder',
            'AXYTOSKAUFAUFRECHNUNGREPORTEDTRACKINGCODE',
            "VARCHAR(128) NOT NULL DEFAULT ''"
        );
    }

    /**
     * @return void
     */
    private static function addOrderBasketHash()
    {
        self::addTableColumn(
            'oxorder',
            'AXYTOSKAUFAUFRECHNUNGORDERBASKETHASH',
            "VARCHAR(64) NOT NULL DEFAULT ''" // possible hash sha256 with 64 chars, but not sha512!
        );
    }

    /**
     * @return void
     */
    private static function addOrderState()
    {
        self::addTableColumn(
            'oxorder',
            'AXYTOSKAUFAUFRECHNUNGORDERSTATE',
            'TEXT'
        );
    }

    /**
     * @return void
     */
    private static function addOrderStateData()
    {
        self::addTableColumn(
            'oxorder',
            'AXYTOSKAUFAUFRECHNUNGORDERSTATEDATA',
            'TEXT'
        );
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param string $definition
     *
     * @return void
     */
    private static function addTableColumn($tableName, $columnName, $definition)
    {
        $database = \oxDb::getDb();
        $row = $database->getRow(
            'SELECT COLUMN_NAME ' .
                'FROM INFORMATION_SCHEMA.COLUMNS ' .
                'WHERE TABLE_SCHEMA = DATABASE() ' .
                'AND TABLE_NAME = ? ' .
                'AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        );

        if ([] !== $row) {
            return;
        }

        $database->execute("ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$definition}");
    }
}
