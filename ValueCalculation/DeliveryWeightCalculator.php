<?php

namespace Axytos\KaufAufRechnung_OXID5\ValueCalculation;

use oxList;
use oxOrder;

class DeliveryWeightCalculator
{
    /**
     * @param oxOrder $order
     * @return float
     */
    public function calculate($order)
    {
        /** @var oxList */
        $orderArticleList = $order->getOrderArticles();
        $orderArticles = array_values($orderArticleList->getArray());

        $weight = 0.0;

        foreach ($orderArticles as &$orderArticle) {
            $weight += floatval($orderArticle->getFieldData("oxweight")) * floatval($orderArticle->getFieldData("oxamount"));
        }
        return $weight;
    }
}
