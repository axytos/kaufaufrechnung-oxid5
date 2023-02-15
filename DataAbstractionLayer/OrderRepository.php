<?php

namespace Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer;

use Axytos\ECommerce\Order\OrderCheckProcessStates;
use Axytos\KaufAufRechnung_OXID5\Events\AxytosEvents;
use oxDb;
use oxOrder;

class OrderRepository
{
    /**
     * @param string $orderId
     * @return oxOrder|null
     */
    public function findOrder($orderId)
    {
        /** @var oxOrder */
        $order = oxNew("oxorder");
        if ($order->load($orderId)) {
            return $order;
        } else {
            return null;
        }
    }

    /**
     * @return oxOrder[]
     */
    public function findAllToSync()
    {
        $filters = [
            "axytoskaufaufrechnungcancelreported = 0 AND oxstorno = 1",
            "axytoskaufaufrechnungcreateinvoicereported = 0 AND oxbillnr IS NOT NULL AND oxbillnr != ''",
            "axytoskaufaufrechnungshippingreported = 0 AND oxsenddate != '' AND oxsenddate != '-' AND oxsenddate != '0000-00-00 00:00:00'",
            "axytoskaufaufrechnungreportedtrackingcode != oxtrackcode"
        ];
        $query = "SELECT oxid FROM oxorder " .
            "WHERE oxpaymenttype = ? AND axytoskaufaufrechnungordercheckprocessstatus = ? AND " .
            "((" . implode(") OR (", $filters) . "))";

        $orderIds = oxDb::getDb()->getCol($query, [
            AxytosEvents::PAYMENT_METHOD_ID,
            OrderCheckProcessStates::CONFIRMED,
        ]);

        $orders = array_map([$this, 'findOrder'], $orderIds);
        return array_filter($orders);
    }

    /**
     * @return oxOrder[]
     */
    public function findAllToUpdate()
    {
        $query = "SELECT oxid FROM oxorder " .
            "WHERE oxpaymenttype = ? " .
            "AND axytoskaufaufrechnungordercheckprocessstatus = ? " .
            "AND axytoskaufaufrechnungcreateinvoicereported = ?";

        $orderIds = oxDb::getDb()->getCol($query, [
            AxytosEvents::PAYMENT_METHOD_ID,
            OrderCheckProcessStates::CONFIRMED,
            0,
        ]);

        $orders = array_map([$this, 'findOrder'], $orderIds);
        return array_filter($orders);
    }

    /**
     * @param oxOrder $order
     * @return string
     */
    public function findLogistician($order)
    {
        $db = oxDb::getDb();
        $value = $db->getOne(
            'SELECT oxtitle FROM oxdeliveryset WHERE oxid = ?',
            [$order->getFieldData("oxdeltype")]
        );

        return strval($value) ?: "";
    }
}
