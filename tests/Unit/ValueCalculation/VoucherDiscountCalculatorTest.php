<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests\Unit\ValueCalculation;

use Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class VoucherDiscountCalculatorTest extends TestCase
{
    /**
     * @var VoucherDiscountCalculator
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
        $this->sut = new VoucherDiscountCalculator();
    }

    /**
     * @param mixed $oxvoucherdiscount
     *
     * @return void
     *
     * @dataProvider getZeroValues
     */
    #[DataProvider('getZeroValues')]
    public function test_calculate_returns_zero_for_zero_values($oxvoucherdiscount)
    {
        $order = $this->createOrderMock($oxvoucherdiscount);

        $actual = $this->sut->calculate($order);

        $this->assertTrue(is_float($actual));
        $this->assertSame(0.0, $actual);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function getZeroValues()
    {
        // [oxvoucherdiscount]
        return [
            [intval(0)],
            [floatval(0)],
            [''],
            [false],
            [null],
        ];
    }

    /**
     * @return void
     */
    public function test_calculate_returns_negative_value_for_non_zero_integer_voucher_discount()
    {
        $order = $this->createOrderMock(intval(42));

        $actual = $this->sut->calculate($order);

        $this->assertTrue(is_float($actual));
        $this->assertSame(-42.0, $actual);
    }

    /**
     * @return void
     */
    public function test_calculate_returns_negative_value_for_non_zero_float_voucher_discount()
    {
        $order = $this->createOrderMock(42.36);

        $actual = $this->sut->calculate($order);

        $this->assertTrue(is_float($actual));
        $this->assertSame(-42.36, $actual);
    }

    /**
     * @param mixed $oxvoucherdiscount
     *
     * @return \oxOrder&MockObject
     */
    private function createOrderMock($oxvoucherdiscount)
    {
        /** @var \oxOrder&MockObject */
        $order = $this->createMock(\oxOrder::class);
        $order->method('getFieldData')->willReturnCallback(function ($field) use ($oxvoucherdiscount) {
            switch ($field) {
                case 'oxvoucherdiscount':
                    return $oxvoucherdiscount;
            }

            return null;
        });

        return $order;
    }
}
