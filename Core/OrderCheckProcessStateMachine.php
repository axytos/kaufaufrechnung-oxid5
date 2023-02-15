<?php

namespace Axytos\KaufAufRechnung_OXID5\Core;

use Axytos\ECommerce\Order\OrderCheckProcessStates;
use oxField;
use oxOrder;

class OrderCheckProcessStateMachine
{
    /**
     * @param oxOrder $order
     * @return string|null
     */
    public function getState($order)
    {
        /** @phpstan-ignore-next-line */
        return $order->oxorder__axytoskaufaufrechnungordercheckprocessstatus->value;
    }

    /**
     * @param oxOrder $order
     * @return void
     */
    public function setUnchecked($order)
    {
        $this->updateState($order, OrderCheckProcessStates::UNCHECKED);
    }

    /**
     * @param oxOrder $order
     * @param string $orderBasketHash
     * @return void
     */
    public function setChecked($order, $orderBasketHash)
    {
        /** @phpstan-ignore-next-line */
        $order->oxorder__axytoskaufaufrechnungorderbaskethash = new oxField($orderBasketHash);
        $this->updateState($order, OrderCheckProcessStates::CHECKED);
    }

    /**
     * @param oxOrder $order
     * @return void
     */
    public function setConfirmed($order)
    {
        $this->updateState($order, OrderCheckProcessStates::CONFIRMED);
    }

    /**
     * @param oxOrder $order
     * @return void
     */
    public function setFailed($order)
    {
        $this->updateState($order, OrderCheckProcessStates::FAILED);
    }

    /**
     * @param oxOrder $order
     * @param string $orderCheckProcessState
     * @return void
     */
    private function updateState($order, $orderCheckProcessState)
    {
        $orderCheckProcessState = (string) $orderCheckProcessState;
        /** @phpstan-ignore-next-line */
        $order->oxorder__axytoskaufaufrechnungordercheckprocessstatus = new oxField($orderCheckProcessState);
        /** @phpstan-ignore-next-line */
        $order->save();
    }
}
