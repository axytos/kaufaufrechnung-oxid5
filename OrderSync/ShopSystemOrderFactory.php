<?php

namespace Axytos\KaufAufRechnung_OXID5\OrderSync;

use Axytos\ECommerce\OrderSync\OrderHashCalculator;
use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContextFactory;
use oxOrder;

class ShopSystemOrderFactory
{
    /**
     * @var InvoiceOrderContextFactory
     */
    private $invoiceOrderContextFactory;

    /**
     * @var OrderHashCalculator
     */
    private $orderHashCalculator;

    public function __construct(
        InvoiceOrderContextFactory $invoiceOrderContextFactory,
        OrderHashCalculator $orderHashCalculator
    ) {
        $this->invoiceOrderContextFactory = $invoiceOrderContextFactory;
        $this->orderHashCalculator = $orderHashCalculator;
    }

    /**
     * @param \oxOrder $order
     * @return \Axytos\ECommerce\OrderSync\ShopSystemOrderInterface
     */
    public function create($order)
    {
        return new ShopSystemOrder(
            $order,
            $this->invoiceOrderContextFactory,
            $this->orderHashCalculator
        );
    }

    /**
     * @param \oxOrder[] $orders
     * @return \Axytos\ECommerce\OrderSync\ShopSystemOrderInterface[]
     */
    public function createMany($orders)
    {
        return array_map([$this, 'create'], $orders);
    }
}
