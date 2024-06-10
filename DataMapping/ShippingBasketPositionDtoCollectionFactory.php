<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDtoCollection;
use oxOrder;
use oxList;

class ShippingBasketPositionDtoCollectionFactory
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\ShippingBasketPositionDtoFactory
     */
    private $shippingBasketPositionDtoFactory;

    public function __construct(ShippingBasketPositionDtoFactory $shippingBasketPositionDtoFactory)
    {
        $this->shippingBasketPositionDtoFactory = $shippingBasketPositionDtoFactory;
    }

    /**
     * @param oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDtoCollection
     */
    public function create($order)
    {
        /** @var oxList */
        $orderArticles = $order->getOrderArticles();
        $positions = array_map([$this->shippingBasketPositionDtoFactory, 'create'], array_values($orderArticles->getArray()));

        array_push($positions, $this->shippingBasketPositionDtoFactory->createShippingPosition());

        return new ShippingBasketPositionDtoCollection(...$positions);
    }
}
