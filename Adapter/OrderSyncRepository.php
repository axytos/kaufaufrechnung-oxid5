<?php

namespace Axytos\KaufAufRechnung_OXID5\Adapter;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\OrderSyncRepositoryInterface;
use Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository;

class OrderSyncRepository implements OrderSyncRepositoryInterface
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository
     */
    private $orderRepository;

    /**
     * @var \Axytos\KaufAufRechnung_OXID5\Adapter\PluginOrderFactory
     */
    private $pluginOrderFactory;

    /**
     *
     * @param \Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository $orderRepository
     * @param \Axytos\KaufAufRechnung_OXID5\Adapter\PluginOrderFactory $pluginOrderFactory
     * @return void
     */
    public function __construct(OrderRepository $orderRepository, PluginOrderFactory $pluginOrderFactory)
    {
        $this->orderRepository = $orderRepository;
        $this->pluginOrderFactory = $pluginOrderFactory;
    }

    public function getOrdersByStates($orderStates, $limit = null, $startId = null)
    {
        $this->orderRepository->migrateOrderStates();
        $orders = $this->orderRepository->getOrdersByStates($orderStates, $limit, $startId);
        return $this->pluginOrderFactory->createMany($orders);
    }

    public function getOrderByOrderNumber($orderNumber)
    {
        $order = $this->orderRepository->getOrderByOrderNumber($orderNumber);

        if (is_null($order)) {
            return null;
        }

        return $this->pluginOrderFactory->create($order);
    }
}
