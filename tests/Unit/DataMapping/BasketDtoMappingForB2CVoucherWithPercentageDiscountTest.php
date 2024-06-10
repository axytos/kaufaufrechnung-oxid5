<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests\Unit\DataMapping;

use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\BasketPositionDtoFactory;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator;
use oxOrder;
use oxOrderArticle;
use oxList;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasketDtoMappingForB2CVoucherWithPercentageDiscountTest extends BasketDtoMappingTestCase
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
     *  =======================
     *
     *  Voucher             =  10.00 %
     *
     *  PositionVAT         =  19.00 %
     *  PositionAmount      =  50.00
     *  PositionUnitNetto   =   8.71 EUR = 10.36 EUR /  1.19
     *  PositionUnitBrutto  =  10.36 EUR =  8.71 EUR *  1.19
     *  PositionNetto       = 435.50 EUR =  8.71 EUR * 50.00
     *  PositionBrutto      = 519.00 EUR = 10.36 EUR * 50.00
     *
     *  DeliveryVAT         =  19.00 %
     *  DeliveryNetto       =   6.50 EUR = 7.74 EUR /  1.19
     *  DeliveryBrutto      =   7.74 EUR = 6.50 EUR *  1.19
     *
     *  BasketNetto EXCLUDES VOUCHER FOR B2C
     *  BasketNetto         = 442.00 EUR = 435.50 EUR + 6.50 EUR
     *                                   = PositionNetto + DeliveryNetto
     *
     *  BasketBrutto        = 474.84 EUR = 519.00 EUR * (1 - 0.10) + 7.74 EUR
     *                                   = PositionBrutto * (1 - Voucher) + Delivery Brutto
     */

    /** @var array<string,mixed> */
    private $orderData = [
        'oxcurrency' => 'EUR',
        'oxtotalordersum' => 474.84,
        'oxtotalnetsum' => 435.50,  // position netto / excludes shipping
        'oxvoucherdiscount' => 51.9, // 51.9 EUR = total monetary discount of all vouchers
        'oxdelcost' => 7.74,
        'oxdelvat' => 19,
        'oxisnettomode' => 0,
    ];
    /** @var array<array<string,mixed>> */
    private $articleData = [
        0 => [
            'oxartnum' => '162122.05.4000',
            'oxtitle' => 'Powerback Slim Fit Rot 4000 mAh',
            'oxamount' => 50,
            'oxbrutprice' => 519.00,
            'oxnetprice' => 435.50,
            'oxvat' => 19,
            'oxnprice' => 8.71,
            'oxbprice' => 10.36,
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
        $this->assertEquals(442.00, $basketDto->netTotal);
        $this->assertEquals(474.84, $basketDto->grossTotal);
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
        $this->assertEquals('162122.05.4000', $positions['162122.05.4000']->productId);
        $this->assertEquals('Powerback Slim Fit Rot 4000 mAh', $positions['162122.05.4000']->productName);
        $this->assertEquals(50, $positions['162122.05.4000']->quantity);
        $this->assertEquals(519.00, $positions['162122.05.4000']->grossPositionTotal);
        $this->assertEquals(435.50, $positions['162122.05.4000']->netPositionTotal);
        $this->assertEquals(19, $positions['162122.05.4000']->taxPercent);
        $this->assertEquals(8.71, $positions['162122.05.4000']->netPricePerUnit);
        $this->assertEquals(10.36, $positions['162122.05.4000']->grossPricePerUnit);
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
        $this->assertEquals(-51.9, $basketPositionForVoucher->grossPositionTotal);
        $this->assertEquals(0, $basketPositionForVoucher->netPositionTotal);
        $this->assertEquals(0, $basketPositionForVoucher->taxPercent);
        $this->assertEquals(0, $basketPositionForVoucher->netPricePerUnit);
        $this->assertEquals(-51.9, $basketPositionForVoucher->grossPricePerUnit);
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
