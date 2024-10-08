<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\ShippingBasketPositionDtoCollection;

class ShippingBasketPositionDtoCollectionFactory
{
    /**
     * @var ShippingBasketPositionDtoFactory
     */
    private $shippingBasketPositionDtoFactory;

    public function __construct(ShippingBasketPositionDtoFactory $shippingBasketPositionDtoFactory)
    {
        $this->shippingBasketPositionDtoFactory = $shippingBasketPositionDtoFactory;
    }

    /**
     * @param \oxOrder $order
     *
     * @return ShippingBasketPositionDtoCollection
     */
    public function create($order)
    {
        /** @var \oxList */
        $orderArticles = $order->getOrderArticles();
        $positions = array_map([$this->shippingBasketPositionDtoFactory, 'create'], array_values($orderArticles->getArray()));

        array_push($positions, $this->shippingBasketPositionDtoFactory->createShippingPosition());

        return new ShippingBasketPositionDtoCollection(...$positions);
    }
}
