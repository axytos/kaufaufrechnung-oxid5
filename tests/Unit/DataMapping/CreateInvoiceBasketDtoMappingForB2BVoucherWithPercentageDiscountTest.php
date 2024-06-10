<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests\Unit\DataMapping;

use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketPositionDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceTaxGroupDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceTaxGroupDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator;
use oxOrder;
use oxOrderArticle;
use oxList;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateInvoiceBasketDtoMappingForB2BVoucherWithPercentageDiscountTest extends BasketDtoMappingTestCase
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketDtoFactory
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
        $createInvoicebasketPositionDtoFactory = new CreateInvoiceBasketPositionDtoFactory($shippingCostCalculator, $voucherDiscountCalculator);
        $createInvoicebasketPositionDtoCollectionFactory = new CreateInvoiceBasketPositionDtoCollectionFactory($createInvoicebasketPositionDtoFactory);
        $createInvoiceTaxGroupDtoFactory = new CreateInvoiceTaxGroupDtoFactory($shippingCostCalculator, $voucherDiscountCalculator);
        $createInvoiceTaxGroupDtoCollectionFactory = new CreateInvoiceTaxGroupDtoCollectionFactory($createInvoiceTaxGroupDtoFactory);
        $this->sut = new CreateInvoiceBasketDtoFactory(
            $createInvoicebasketPositionDtoCollectionFactory,
            $createInvoiceTaxGroupDtoCollectionFactory,
            $shippingCostCalculator
        );
    }

    /**
     *  For B2B Netto Vouchers
     *  ======================
     *
     *  Voucher                    =  10.00 %
     *
     *  PositionVAT                =  19.00 %
     *  PositionAmount             =  25.00
     *  PositionUnitNetto          =   3.98 EUR = 4.74 EUR /  1.19
     *  PositionUnitBrutto         =   4.74 EUR = 3.98 EUR *  1.19
     *  PositionNetto              =  99.50 EUR = 3.98 EUR * 25.00
     *  PositionBrutto             = 118.50 EUR = 4.74 EUR * 25.00
     *
     *  DeliveryVAT                =  19.00 %
     *  DeliveryNetto              =   6.50 EUR = 7.74 EUR /  1.19
     *  DeliveryBrutto             =   7.74 EUR = 6.50 EUR *  1.19
     *
     *  BasketNetto                =  96.05 EUR = (99.50 EUR * (1 - 00.10)) + 6.50 EUR
     *                                          = (PositionNetto * (1 - Voucher)) + DeliveryNetto
     *
     *  BasketBrutto               = 114.30 EUR = (99.50 EUR * (1 - 00.10)) * 1.19 + 7.74 EUR
     *                                          = (PositionNetto * (1 - Voucher)) * VAT + Delivery Brutto
     *
     *  TaxGroup[19.00].ValueToTax = 106.00 EUR = 99.50 EUR + 6.50 EUR
     *                                          = PositionNetto + DeliveryNetto
     *  TaxGroup[19.00].Total      =  20.24 EUR = (118.50 EUR - 99.50 EUR) + (7.74 EUR - 6.50 EUR)
     *                                          = (PositionBrutto - PositionNetto) + (DeliveryBrutto - DeliveryNetto)
     *
     *  TaxGroup[00.00].ValueToTax =  -9.95 EUR [voucher is subtracted from NETTO]
     *  TaxGroup[00.00].Total      =   0.00 EUR [voucher is subtracted from NETTO]
     */


    /** @var array<string,mixed> */
    private $orderData = [
        'oxcurrency' => 'EUR',
        'oxtotalordersum' => 114.30,
        'oxtotalnetsum' => 99.50, // position netto / excludes shipping
        'oxvoucherdiscount' => 9.95, // 9.95 EUR = total monetary discount of all vouchers
        'oxdelcost' => 7.74,
        'oxdelvat' => 19,
        'oxisnettomode' => 1,
    ];

    /** @var array<array<string,mixed>> */
    private $articleData = [
        0 => [
            'oxartnum' => 'U10251.05.01',
            'oxtitle' => 'USB Stick Flash Band Rot 128MB',
            'oxamount' => 25,
            'oxbrutprice' => 118.50,
            'oxnetprice' => 99.50,
            'oxvat' => 19,
            'oxnprice' => 3.98,
            'oxbprice' => 4.74,
        ],
    ];


    /**
     * @return void
     */
    public function test_mapping_of_basket_totals()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $this->assertEquals(96.05, $createInvoiceBasketDto->netTotal);
        $this->assertEquals(114.30, $createInvoiceBasketDto->grossTotal);
        $this->assertNotNull($createInvoiceBasketDto->positions);
    }

    /**
     * @return void
     */
    public function test_mapping_of_article_positions()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $positions = $this->getCreateInvoiceBasketPositionsForArticlesByProductId($createInvoiceBasketDto);
        $this->assertEquals('U10251.05.01', $positions['U10251.05.01']->productId);
        $this->assertEquals('USB Stick Flash Band Rot 128MB', $positions['U10251.05.01']->productName);
        $this->assertEquals(25, $positions['U10251.05.01']->quantity);
        $this->assertEquals(118.50, $positions['U10251.05.01']->grossPositionTotal);
        $this->assertEquals(99.50, $positions['U10251.05.01']->netPositionTotal);
        $this->assertEquals(19, $positions['U10251.05.01']->taxPercent);
        $this->assertEquals(3.98, $positions['U10251.05.01']->netPricePerUnit);
        $this->assertEquals(4.74, $positions['U10251.05.01']->grossPricePerUnit);
    }

    /**
     * @return void
     */
    public function test_mapping_of_shipping_position()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $createInvoiceBasketPositionForShipping = $this->getCreateInvoiceBasketPositionForShipping($createInvoiceBasketDto);
        $this->assertEquals('0', $createInvoiceBasketPositionForShipping->productId);
        $this->assertEquals('Shipping', $createInvoiceBasketPositionForShipping->productName);
        $this->assertEquals(1, $createInvoiceBasketPositionForShipping->quantity);
        $this->assertEquals(7.74, $createInvoiceBasketPositionForShipping->grossPositionTotal);
        $this->assertEquals(6.50, $createInvoiceBasketPositionForShipping->netPositionTotal);
        $this->assertEquals(19, $createInvoiceBasketPositionForShipping->taxPercent);
        $this->assertEquals(6.50, $createInvoiceBasketPositionForShipping->netPricePerUnit);
        $this->assertEquals(7.74, $createInvoiceBasketPositionForShipping->grossPricePerUnit);
    }

    /**
     * @return void
     */
    public function test_mapping_of_voucher_position()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $createInvoiceBasketPositionForVoucher = $this->getCreateInvoiceBasketPositionForVoucher($createInvoiceBasketDto);
        $this->assertEquals('oxvoucherdiscount', $createInvoiceBasketPositionForVoucher->productId);
        $this->assertEquals('Voucher', $createInvoiceBasketPositionForVoucher->productName);
        $this->assertEquals(1, $createInvoiceBasketPositionForVoucher->quantity);
        $this->assertEquals(0, $createInvoiceBasketPositionForVoucher->grossPositionTotal);
        $this->assertEquals(-9.95, $createInvoiceBasketPositionForVoucher->netPositionTotal);
        $this->assertEquals(0, $createInvoiceBasketPositionForVoucher->taxPercent);
        $this->assertEquals(-9.95, $createInvoiceBasketPositionForVoucher->netPricePerUnit);
        $this->assertEquals(0, $createInvoiceBasketPositionForVoucher->grossPricePerUnit);
    }

    /**
     * @return void
     */
    public function test_textrate_of_voucher_position_is_zero()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $createInvoiceBasketPositionForVoucher = $this->getCreateInvoiceBasketPositionForVoucher($createInvoiceBasketDto);

        $this->assertEquals(0.0, $createInvoiceBasketPositionForVoucher->taxPercent);
    }

    /**
     * @return void
     */
    public function test_mapping_of_taxgroups()
    {
        /** @var oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $taxGroups = $this->getCreateInvoiceTaxGroupByTaxPercent($createInvoiceBasketDto);

        $this->assertCount(2, $taxGroups);

        $this->assertEquals(-9.95, $taxGroups[0]->valueToTax);
        $this->assertEquals(0, $taxGroups[0]->total);

        $this->assertEquals(106.00, $taxGroups[19]->valueToTax);
        $this->assertEquals(20.24, $taxGroups[19]->total);
    }
}
