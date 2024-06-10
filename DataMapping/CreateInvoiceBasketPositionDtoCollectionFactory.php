<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDtoCollection;
use oxOrder;
use oxList;

class CreateInvoiceBasketPositionDtoCollectionFactory
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketPositionDtoFactory
     */
    private $createInvoiceBasketPositionDtoFactory;

    public function __construct(CreateInvoiceBasketPositionDtoFactory $createInvoiceBasketPositionDtoFactory)
    {
        $this->createInvoiceBasketPositionDtoFactory = $createInvoiceBasketPositionDtoFactory;
    }

    /**
     * @param oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDtoCollection
     */
    public function create($order)
    {
        /** @var oxList */
        $orderArticles = $order->getOrderArticles();
        $positions = array_map([$this->createInvoiceBasketPositionDtoFactory, 'create'], array_values($orderArticles->getArray()));

        $voucherPosition = $this->createInvoiceBasketPositionDtoFactory->createVoucherPosition($order, $positions);
        if (!is_null($voucherPosition)) {
            array_push($positions, $voucherPosition);
        }

        $shippingPosition = $this->createInvoiceBasketPositionDtoFactory->createShippingPosition($order);
        array_push($positions, $shippingPosition);

        return new CreateInvoiceBasketPositionDtoCollection(...$positions);
    }
}
