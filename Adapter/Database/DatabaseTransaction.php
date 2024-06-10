<?php

namespace Axytos\KaufAufRechnung_OXID5\Adapter\Database;

use Axytos\KaufAufRechnung\Core\Plugin\Abstractions\Database\DatabaseTransactionInterface;
use Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository;

class DatabaseTransaction implements DatabaseTransactionInterface
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository
     */
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function begin()
    {
        $this->orderRepository->startTransaction();
    }

    public function commit()
    {
        $this->orderRepository->commitTransaction();
    }

    public function rollback()
    {
        $this->orderRepository->rollbackTransaction();
    }
}
