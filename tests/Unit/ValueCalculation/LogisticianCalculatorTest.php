<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests\Unit\ValueCalculation;

use Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\LogisticianCalculator;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class LogisticianCalculatorTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataAbstractionLayer\OrderRepository&MockObject
     */
    private $orderRepository;

    /**
     * @var LogisticianCalculator
     */
    private $sut;

    /**
     * @before
     *
     * @return void
     */
    #[Before]
    public function beforeEach()
    {
        $this->orderRepository = $this->createMock(OrderRepository::class);

        $this->sut = new LogisticianCalculator($this->orderRepository);
    }

    /**
     * @return void
     */
    public function test_calculate()
    {
        /** @var \oxOrder&MockObject */
        $order = $this->createMock(\oxOrder::class);

        $this->orderRepository->method('findLogistician')->with($order)->willReturn('Logistician');

        $result = $this->sut->calculate($order);

        $this->assertEquals('Logistician', $result);
    }
}
