<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDto;
use oxOrderArticle;

class ShippingBasketPositionDtoFactory
{
    /**
     * @param oxOrderArticle $shippingItem
     * @return \Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDto
     */
    public function create($shippingItem)
    {
        $position = new ShippingBasketPositionDto();
        $position->productId = strval($shippingItem->getFieldData("oxartnum"));
        $position->quantity = intval($shippingItem->getFieldData("oxamount"));
        return $position;
    }

    /**
     * @return \Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDto
     */
    public function createShippingPosition()
    {
        $position = new ShippingBasketPositionDto();
        $position->productId = '0';
        $position->quantity = 1;
        return $position;
    }
}
