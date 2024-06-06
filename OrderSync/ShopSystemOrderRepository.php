<?php

namespace Axytos\KaufAufRechnung_OXID5\OrderSync;

use Axytos\ECommerce\OrderSync\ShopSystemOrderRepositoryInterface;
use Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository;

class ShopSystemOrderRepository implements ShopSystemOrderRepositoryInterface
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var ShopSystemOrderFactory
     */
    private $shopSystemOrderFactory;

    public function __construct(
        OrderRepository $orderRepository,
        ShopSystemOrderFactory $shopSystemOrderFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->shopSystemOrderFactory = $shopSystemOrderFactory;
    }

    /**
     * @return \Axytos\ECommerce\OrderSync\ShopSystemOrderInterface[]
     */
    public function getOrdersToSync()
    {
        $orders = $this->orderRepository->findAllToSync();
        return $this->shopSystemOrderFactory->createMany($orders);
    }

    /**
     * @return \Axytos\ECommerce\OrderSync\ShopSystemOrderInterface[]
     */
    public function getOrdersToUpdate()
    {
        $orders = $this->orderRepository->findAllToUpdate();
        return $this->shopSystemOrderFactory->createMany($orders);
    }
}
