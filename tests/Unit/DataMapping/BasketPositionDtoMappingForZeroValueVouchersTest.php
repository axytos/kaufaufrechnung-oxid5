<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests\Unit\DataMapping;

use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketPositionDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketPositionDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceTaxGroupDtoFactory;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BasketPositionDtoMappingForZeroValueVouchersTest extends TestCase
{
    /**
     * @param bool  $oxisnettomode
     * @param mixed $oxvoucherdiscount
     *
     * @return void
     *
     * @dataProvider getZeroValues
     */
    #[DataProvider('getZeroValues')]
    public function test_basket_position_dto_factory($oxisnettomode, $oxvoucherdiscount)
    {
        $sut = new BasketPositionDtoFactory(new ShippingCostCalculator(), new VoucherDiscountCalculator());
        $order = $this->createOrderMock($oxisnettomode, $oxvoucherdiscount);
        $result = $sut->createVoucherPosition($order, []);
        $this->assertNull($result);
    }

    /**
     * @param bool  $oxisnettomode
     * @param mixed $oxvoucherdiscount
     *
     * @return void
     *
     * @dataProvider getZeroValues
     */
    #[DataProvider('getZeroValues')]
    public function test_create_invoice_basket_position_dto_factory($oxisnettomode, $oxvoucherdiscount)
    {
        $sut = new CreateInvoiceBasketPositionDtoFactory(new ShippingCostCalculator(), new VoucherDiscountCalculator());
        $order = $this->createOrderMock($oxisnettomode, $oxvoucherdiscount);
        $result = $sut->createVoucherPosition($order, []);
        $this->assertNull($result);
    }

    /**
     * @param bool  $oxisnettomode
     * @param mixed $oxvoucherdiscount
     *
     * @return void
     *
     * @dataProvider getZeroValues
     */
    #[DataProvider('getZeroValues')]
    public function test_create_invoice_tax_group_dto_factory($oxisnettomode, $oxvoucherdiscount)
    {
        $sut = new CreateInvoiceTaxGroupDtoFactory(new ShippingCostCalculator(), new VoucherDiscountCalculator());
        $order = $this->createOrderMock($oxisnettomode, $oxvoucherdiscount);
        $result = $sut->createVoucherPosition($order);
        $this->assertNull($result);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function getZeroValues()
    {
        // [oxisnettomode, oxvoucherdiscount]
        return [
            [true, intval(0)],
            [true, floatval(0)],
            [true, ''],
            [true, false],
            [true, null],

            [false, intval(0)],
            [false, floatval(0)],
            [false, ''],
            [false, false],
            [false, null],
        ];
    }

    /**
     * @param mixed $oxisnettomode
     * @param mixed $oxvoucherdiscount
     *
     * @return \oxOrder&MockObject
     */
    private function createOrderMock($oxisnettomode, $oxvoucherdiscount)
    {
        /** @var \oxOrder&MockObject */
        $order = $this->createMock(\oxOrder::class);
        $order->method('getFieldData')->willReturnCallback(function ($field) use ($oxisnettomode, $oxvoucherdiscount) {
            switch ($field) {
                case 'oxisnettomode':
                    return $oxisnettomode;
                case 'oxvoucherdiscount':
                    return $oxvoucherdiscount;
            }

            return null;
        });

        return $order;
    }
}
