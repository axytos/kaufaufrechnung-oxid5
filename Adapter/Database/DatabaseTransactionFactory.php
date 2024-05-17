<?php

namespace Axytos\KaufAufRechnung_OXID5\Adapter\Database;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Database\DatabaseTransactionFactoryInterface;
use Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository;

class DatabaseTransactionFactory implements DatabaseTransactionFactoryInterface
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository
     */
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return \Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Database\DatabaseTransactionInterface
     */
    public function create()
    {
        return new DatabaseTransaction($this->orderRepository);
    }
}
