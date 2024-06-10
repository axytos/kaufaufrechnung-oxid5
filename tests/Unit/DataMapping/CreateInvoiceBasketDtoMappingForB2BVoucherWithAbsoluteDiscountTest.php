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

class CreateInvoiceBasketDtoMappingForB2BVoucherWithAbsoluteDiscountTest extends BasketDtoMappingTestCase
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
     *  Voucher                    =  5.00 EUR
     *  VoucherVAT                 =  0.00 %    Voucher has no tax rate
     *
     *  PositionVAT                = 19.00 %
     *  PositionAmount             = 25.00
     *  PositionUnitNetto          =  3.28 EUR = 3.90 EUR /  1.19
     *  PositionUnitBrutto         =  3.90 EUR = 3.28 EUR *  1.19
     *  PositionNetto              = 82.00 EUR = 3.28 EUR * 25.00
     *  PositionBrutto             = 97.50 EUR = 3.90 EUR * 25.00
     *
     *  DeliveryVAT                = 19.00 %
     *  DeliveryNetto              =  6.50 EUR = 7.74 EUR /  1.19
     *  DeliveryBrutto             =  7.74 EUR = 6.50 EUR *  1.19
     *
     *  BasketNetto                = 83.50 EUR = (82.00 EUR - 5.00 EUR) + 6.50 EUR
     *                                         = (PositionNetto - Voucher) + DeliveryNetto
     *
     *  BasketBrutto               = 99.37 EUR = (82.00 EUR - 5.00 EUR) * 1.19 + 7.74 EUR
     *                                         = (PositionNetto - Voucher) * VAT + Delivery Brutto
     *
     *  TaxGroup[19.00].ValueToTax = 88.50 EUR = 82.00 EUR + 6.50 EUR
     *                                         = PositionNetto + DeliveryNetto
     *  TaxGroup[19.00].Total      = 16.82 EUR = (97.50 EUR - 82.00 EUR) + (7.74 EUR - 6.5 EUR)
     *                                         = (PositionBrutto - PositionNetto) + (DeliveryBrutto - DeliveryNetto)
     *
     *  TaxGroup[00.00].ValueToTax = -5.00 EUR [voucher is subtracted from NETTO]
     *  TaxGroup[00.00].Total      =  0.00 EUR [voucher is subtracted from NETTO]
     */

    /** @var array<string,mixed> */
    private $orderData = [
        'oxcurrency' => 'EUR',
        'oxtotalordersum' => 99.37,
        'oxtotalbrutsum' => 99.37,
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

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $this->assertEquals(83.50, $createInvoiceBasketDto->netTotal);
        $this->assertEquals(99.37, $createInvoiceBasketDto->grossTotal);
        $this->assertNotNull($createInvoiceBasketDto->taxGroups);
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

        /** @var array<mixed,\Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto>  */
        $positions = $this->getCreateInvoiceBasketPositionsForArticlesByProductId($createInvoiceBasketDto);
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
        $this->assertEquals(-5, $createInvoiceBasketPositionForVoucher->netPositionTotal);
        $this->assertEquals(0, $createInvoiceBasketPositionForVoucher->taxPercent);
        $this->assertEquals(-5, $createInvoiceBasketPositionForVoucher->netPricePerUnit);
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

        $this->assertEquals(-5, $taxGroups[0]->valueToTax);
        $this->assertEquals(0, $taxGroups[0]->total);

        $this->assertEquals(88.50, $taxGroups[19]->valueToTax);
        $this->assertEquals(16.74, $taxGroups[19]->total);
    }
}
