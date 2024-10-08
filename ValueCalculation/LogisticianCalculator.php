<?php

namespace Axytos\KaufAufRechnung_OXID5\ValueCalculation;

use Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository;

class LogisticianCalculator
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param \oxOrder $order
     *
     * @return string
     */
    public function calculate($order)
    {
        return $this->orderRepository->findLogistician($order);
    }
}
