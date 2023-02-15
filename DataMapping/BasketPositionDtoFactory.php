<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\BasketPositionDto;
use oxOrder;
use oxOrderArticle;

class BasketPositionDtoFactory
{
    /**
     * @param oxOrderArticle $orderArticle
     * @return \Axytos\ECommerce\DataTransferObjects\BasketPositionDto
     */
    public function create($orderArticle)
    {
        $position = new BasketPositionDto();
        $position->productId = strval($orderArticle->getFieldData("oxartnum"));
        $position->productName = strval($orderArticle->getFieldData("oxtitle"));
        $position->quantity = intval($orderArticle->getFieldData("oxamount"));
        $position->grossPositionTotal = floatval($orderArticle->getFieldData("oxbrutprice"));
        $position->netPositionTotal = floatval($orderArticle->getFieldData("oxnetprice"));
        $position->taxPercent = floatval($orderArticle->getFieldData("oxvat"));
        $position->netPricePerUnit = floatval($orderArticle->getFieldData("oxnprice"));
        $position->grossPricePerUnit = floatval($orderArticle->getFieldData("oxbprice"));

        return $position;
    }

    /**
     * @param oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\BasketPositionDto
     */
    public function createShippingPosition($order)
    {
        $position = new BasketPositionDto();
        $position->productId = '0';
        $position->productName = 'Shipping';
        $position->quantity = 1;
        $position->grossPositionTotal = floatval($order->getFieldData("oxdelcost"));
        $position->netPositionTotal = round(floatval($order->getFieldData("oxdelcost")) * floatval($order->getFieldData("oxdelvat")) / 100, 2);
        $position->taxPercent = floatval($order->getFieldData("oxdelvat"));
        $position->netPricePerUnit = round(floatval($order->getFieldData("oxdelcost")) * floatval($order->getFieldData("oxdelvat")) / 100, 2);
        $position->grossPricePerUnit = floatval($order->getFieldData("oxdelcost"));
        return $position;
    }
}
