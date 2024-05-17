<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests\Unit\DataMapping;

use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketPositionDtoFactory;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use oxOrder;
use oxOrderArticle;
use oxList;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasketDtoMappingForB2BVoucherWithAbsoluteDiscountTest extends BasketDtoMappingTestCase
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\BasketDtoFactory
     */
    private $sut;

    /**
     * @before
     * @return void
     */
    #[Before]
    public function beforeEach()
    {
        $shippingCostCalculator = new ShippingCostCalculator();
        $basketPositionDtoFactory = new BasketPositionDtoFactory($shippingCostCalculator);
        $basketPositionDtoCollectionFactory = new BasketPositionDtoCollectionFactory($basketPositionDtoFactory);
        $this->sut = new BasketDtoFactory(
            $basketPositionDtoCollectionFactory,
            $shippingCostCalculator
        );
    }

    /**
     *  For B2B Netto Vouchers
     *  ======================
     *
     *  Voucher             =  5.00 EUR
     *
     *  PositionVAT         = 19.00 %
     *  PositionAmount      = 25.00
     *  PositionUnitNetto   =  3.28 EUR = 3.90 EUR /  1.19
     *  PositionUnitBrutto  =  3.90 EUR = 3.28 EUR *  1.19
     *  PositionNetto       = 82.00 EUR = 3.28 EUR * 25.00
     *  PositionBrutto      = 97.50 EUR = 3.90 EUR * 25.00
     *
     *  DeliveryVAT         = 19.00 %
     *  DeliveryNetto       =  6.50 EUR = 7.74 EUR /  1.19
     *  DeliveryBrutto      =  7.74 EUR = 6.50 EUR *  1.19
     *
     *  BasketNetto         = 83.50 EUR = (82.00 EUR - 5.00 EUR) + 6.50 EUR
     *                                  = (PositionNetto - Voucher) + DeliveryNetto
     *
     *  BasketBrutto        = 99.37 EUR = (82.00 EUR - 5.00 EUR) * 1.19 + 7.74 EUR
     *                                  = (PositionNetto - Voucher) * VAT + Delivery Brutto
     */


    /** @var array<string,mixed> */
    private $orderData = [
        'oxcurrency' => 'EUR',
        'oxtotalordersum' => 99.37,
        'oxtotalnetsum' => 82.00, // position netto / excludes shipping
        'oxvoucherdiscount' => 5, // 5 EUR = sum of all voucher discounts
        'oxdelcost' => 7.74,
        'oxdelvat' => 19,
        'oxisnettomode' => 1,
    ];

    /** @var array<array<string,mixed>> */
    private $articleData = [
        0 => [
            'oxartnum' => 'U10239.05.01',
            'oxtitle' => 'USB Stick Clip',
            'oxamount' => 25,
            'oxbrutprice' => 97.50,
            'oxnetprice' => 82.00,
            'oxvat' => 19,
            'oxnprice' => 3.28,
            'oxbprice' => 3.90,
        ],
    ];


    /**
     * @return void
     */
    public function test_mapping_of_basket_totals()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketDto */
        $basketDto = $this->sut->create($order);

        $this->assertEquals('EUR', $basketDto->currency);
        $this->assertEquals(83.50, $basketDto->netTotal);
        $this->assertEquals(99.37, $basketDto->grossTotal);
        $this->assertNotNull($basketDto->positions);
    }

    /**
     * @return void
     */
    public function test_mapping_of_article_positions()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketDto */
        $basketDto = $this->sut->create($order);

        /** @var array<mixed,\Axytos\ECommerce\DataTransferObjects\BasketPositionDto>  */
        $positions = $this->getBasketPositionsForArticlesByProductId($basketDto);
        $this->assertEquals('U10239.05.01', $positions['U10239.05.01']->productId);
        $this->assertEquals('USB Stick Clip', $positions['U10239.05.01']->productName);
        $this->assertEquals(25, $positions['U10239.05.01']->quantity);
        $this->assertEquals(97.50, $positions['U10239.05.01']->grossPositionTotal);
        $this->assertEquals(82.00, $positions['U10239.05.01']->netPositionTotal);
        $this->assertEquals(19, $positions['U10239.05.01']->taxPercent);
        $this->assertEquals(3.28, $positions['U10239.05.01']->netPricePerUnit);
        $this->assertEquals(3.90, $positions['U10239.05.01']->grossPricePerUnit);
    }

    /**
     * @return void
     */
    public function test_mapping_of_shipping_position()
    {
        /** @var oxOrder&MockObject */
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
        /** @var oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketDto */
        $basketDto = $this->sut->create($order);

        $basketPositionForVoucher = $this->getBasketPositionForVoucher($basketDto);
        $this->assertEquals('oxvoucherdiscount', $basketPositionForVoucher->productId);
        $this->assertEquals('Voucher', $basketPositionForVoucher->productName);
        $this->assertEquals(1, $basketPositionForVoucher->quantity);
        $this->assertEquals(0, $basketPositionForVoucher->grossPositionTotal);
        $this->assertEquals(-5, $basketPositionForVoucher->netPositionTotal);
        $this->assertEquals(0, $basketPositionForVoucher->taxPercent);
        $this->assertEquals(-5, $basketPositionForVoucher->netPricePerUnit);
        $this->assertEquals(0, $basketPositionForVoucher->grossPricePerUnit);
    }

    /**
     * @return void
     */
    public function test_textrate_of_voucher_position_is_zero()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\BasketDto */
        $basketDto = $this->sut->create($order);

        $basketPositionForVoucher = $this->getBasketPositionForVoucher($basketDto);

        $this->assertEquals(0.0, $basketPositionForVoucher->taxPercent);
    }
}
