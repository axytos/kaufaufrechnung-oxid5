<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\BasketPositionDtoCollection;

class BasketPositionDtoCollectionFactory
{
    /**
     * @var BasketPositionDtoFactory
     */
    private $basketPositionDtoFactory;

    public function __construct(BasketPositionDtoFactory $basketPositionDtoFactory)
    {
        $this->basketPositionDtoFactory = $basketPositionDtoFactory;
    }

    /**
     * @param \oxOrder $order
     *
     * @return BasketPositionDtoCollection
     */
    public function create($order)
    {
        /** @var \oxList */
        $orderArticles = $order->getOrderArticles();
        $positions = array_map([$this->basketPositionDtoFactory, 'create'], array_values($orderArticles->getArray()));

        $voucherPosition = $this->basketPositionDtoFactory->createVoucherPosition($order, $positions);
        if (!is_null($voucherPosition)) {
            array_push($positions, $voucherPosition);
        }

        $shippingPosition = $this->basketPositionDtoFactory->createShippingPosition($order);
        array_push($positions, $shippingPosition);

        return new BasketPositionDtoCollection(...$positions);
    }
}
