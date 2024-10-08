<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests\Unit\DataMapping;

use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketPositionDtoFactory;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
class BasketDtoMappingForB2CVoucherWithAbsoluteDiscountTest extends BasketDtoMappingTestCase
{
    /**
     * @var BasketDtoFactory
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
        $shippingCostCalculator = new ShippingCostCalculator();
        $voucherDiscountCalculator = new VoucherDiscountCalculator();
        $basketPositionDtoFactory = new BasketPositionDtoFactory($shippingCostCalculator, $voucherDiscountCalculator);
        $basketPositionDtoCollectionFactory = new BasketPositionDtoCollectionFactory($basketPositionDtoFactory);
        $this->sut = new BasketDtoFactory(
            $basketPositionDtoCollectionFactory,
            $shippingCostCalculator
        );
    }

    /**
     *  For B2C Brutto Vouchers
     *  =======================.
     *
     *  Voucher             =  5.00 EUR
     *
     *  PositionVAT         = 19.00 %
     *  PositionAmount      =  5.00
     *  PositionUnitNetto   =  7.54 EUR = 8.97 EUR / 1.19
     *  PositionUnitBrutto  =  8.97 EUR = 7.54 EUR * 1.19
     *  PositionNetto       = 37.70 EUR = 7.54 EUR * 5.00
     *  PositionBrutto      = 44.85 EUR = 3.90 EUR * 5.00
     *
     *  DeliveryVAT         = 19.00 %
     *  DeliveryNetto       =  6.50 EUR = 7.74 EUR /  1.19
     *  DeliveryBrutto      =  7.74 EUR = 6.50 EUR *  1.19
     *
     *  BasketNetto EXCLUDES VOUCHER FOR B2C
     *  BasketNetto         = 44.20 EUR = 37.70 EUR + 6.50 EUR
     *                                  = PositionNetto + DeliveryNetto
     *
     *  BasketBrutto        = 46.65 EUR = (37.70 EUR * 1.19) - 5.00 EUR + 7.74 EUR
     *                                  = (PositionNetto * VAT) - Voucher + Delivery Brutto
     *                                  = PositionBrutto - Voucher + Delivery Brutto
     */

    /** @var array<string,mixed> */
    private $orderData = [
        'oxcurrency' => 'EUR',
        'oxtotalordersum' => 46.65,
        'oxtotalnetsum' => 37.70, // position netto / excludes shipping
        'oxvoucherdiscount' => 5, // 5 EUR = sum of all voucher discounts
        'oxdelcost' => 7.74,
        'oxdelvat' => 19,
        'oxisnettomode' => 0,
    ];
    /** @var array<array<string,mixed>> */
    private $articleData = [
        0 => [
            'oxartnum' => '162122L.03.4000',
            'oxtitle' => 'Powerback Slim Fit 4000 mAh EXPRESS',
            'oxamount' => 5.5,
            'oxbrutprice' => 44.85,
            'oxnetprice' => 37.70,
            'oxvat' => 19,
            'oxnprice' => 7.54,
            'oxbprice' => 8.97,
        ],
    ];

    /**
     * @return void
     */
    public function test_mapping_of_basket_totals()
    {
        /** @var \oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketDto */
        $basketDto = $this->sut->create($order);

        $this->assertEquals('EUR', $basketDto->currency);
        $this->assertEquals(44.20, $basketDto->netTotal);
        $this->assertEquals(46.65, $basketDto->grossTotal);
        $this->assertNotNull($basketDto->positions);
    }

    /**
     * @return void
     */
    public function test_mapping_of_article_positions()
    {
        /** @var \oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketDto */
        $basketDto = $this->sut->create($order);

        /** @var array<mixed,\Axytos\ECommerce\DataTransferObjects\BasketPositionDto> */
        $positions = $this->getBasketPositionsForArticlesByProductId($basketDto);
        $this->assertEquals('162122L.03.4000', $positions['162122L.03.4000']->productId);
        $this->assertEquals('Powerback Slim Fit 4000 mAh EXPRESS', $positions['162122L.03.4000']->productName);
        $this->assertEquals(5.5, $positions['162122L.03.4000']->quantity);
        $this->assertEquals(44.85, $positions['162122L.03.4000']->grossPositionTotal);
        $this->assertEquals(37.70, $positions['162122L.03.4000']->netPositionTotal);
        $this->assertEquals(19, $positions['162122L.03.4000']->taxPercent);
        $this->assertEquals(7.54, $positions['162122L.03.4000']->netPricePerUnit);
        $this->assertEquals(8.97, $positions['162122L.03.4000']->grossPricePerUnit);
    }

    /**
     * @return void
     */
    public function test_mapping_of_shipping_position()
    {
        /** @var \oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketDto */
        $basketDto = $this->sut->create($order);

        $basketPositionForShipping = $this->getBasketPositionForShipping($basketDto);
        $this->assertEquals('0', $basketPositionForShipping->productId);
        $this->assertEquals('Shipping', $basketPositionForShipping->productName);
        $this->assertEquals(1, $basketPositionForShipping->quantity);
        $this->assertEquals(7.74, $basketPositionForShipping->grossPositionTotal);
        $this->assertEquals(6.50, $basketPositionForShipping->netPositionTotal);
        $this->assertEquals(19, $basketPositionForShipping->taxPercent);
        $this->assertEquals(6.50, $basketPositionForShipping->netPricePerUnit);
        $this->assertEquals(7.74, $basketPositionForShipping->grossPricePerUnit);
    }

    /**
     * @return void
     */
    public function test_mapping_of_voucher_position()
    {
        /** @var \oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketDto */
        $basketDto = $this->sut->create($order);

        $basketPositionForVoucher = $this->getBasketPositionForVoucher($basketDto);
        $this->assertEquals('oxvoucherdiscount', $basketPositionForVoucher->productId);
        $this->assertEquals('Voucher', $basketPositionForVoucher->productName);
        $this->assertEquals(1, $basketPositionForVoucher->quantity);
        $this->assertEquals(-5, $basketPositionForVoucher->grossPositionTotal);
        $this->assertEquals(0, $basketPositionForVoucher->netPositionTotal);
        $this->assertEquals(0, $basketPositionForVoucher->taxPercent);
        $this->assertEquals(0, $basketPositionForVoucher->netPricePerUnit);
        $this->assertEquals(-5, $basketPositionForVoucher->grossPricePerUnit);
    }

    /**
     * @return void
     */
    public function test_textrate_of_voucher_position_is_zero()
    {
        /** @var \oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketDto */
        $basketDto = $this->sut->create($order);

        $basketPositionForVoucher = $this->getBasketPositionForVoucher($basketDto);

        $this->assertEquals(0.0, $basketPositionForVoucher->taxPercent);
    }
}
