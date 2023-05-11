<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests\Unit\ValueCalculation;

use Axytos\KaufAufRechnung_OXID5\ValueCalculation\TrackingIdCalculator;
use oxOrder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TrackingIdCalculatorTest extends TestCase
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\ValueCalculation\TrackingIdCalculator
     */
    private $sut;

    /**
     * @before
     * @return void
     */
    public function beforeEach()
    {
        $this->sut = new TrackingIdCalculator();
    }

    /**
     * @return void
     */
    public function test_calculate_returns_empty_as_default()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createMock(oxOrder::class);

        $result = $this->sut->calculate($order);

        $this->assertTrue(is_array($result));
        $this->assertTrue($result === []);
    }

    /**
     * @return void
     */
    public function test_calculate_returns_empty_for_empty_tracking_information()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createMock(oxOrder::class);
        $order->method('getFieldData')->with('oxtrackcode')->willReturn('');

        $result = $this->sut->calculate($order);

        $this->assertTrue(is_array($result));
        $this->assertTrue($result === []);
    }

    /**
     * @return void
     */
    public function test_calculate_array_for_non_empty_tracking_information()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createMock(oxOrder::class);
        $order->method('getFieldData')->with('oxtrackcode')->willReturn('tracking code');

        $result = $this->sut->calculate($order);

        $this->assertTrue(is_array($result));
        $this->assertEquals(['tracking code'], $result);
    }
}
