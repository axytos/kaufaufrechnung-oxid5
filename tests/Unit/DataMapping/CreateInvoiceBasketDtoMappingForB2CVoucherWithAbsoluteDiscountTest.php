<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests\Unit\DataMapping;

use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketPositionDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceBasketPositionDtoFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceTaxGroupDtoCollectionFactory;
use Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceTaxGroupDtoFactory;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\ShippingCostCalculator;
use Axytos\KaufAufRechnung_OXID5\ValueCalculation\VoucherDiscountCalculator;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
class CreateInvoiceBasketDtoMappingForB2CVoucherWithAbsoluteDiscountTest extends BasketDtoMappingTestCase
{
    /**
     * @var CreateInvoiceBasketDtoFactory
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
     *  For B2C Brutto Vouchers
     *  =======================.
     *
     *  Voucher                    =  5.00 EUR
     *  VoucherVAT                 =  0.00 %    Voucher has no tax rate
     *
     *  PositionVAT                = 19.00 %
     *  PositionAmount             =  5.00
     *  PositionUnitNetto          =  7.54 EUR = 8.97 EUR / 1.19
     *  PositionUnitBrutto         =  8.97 EUR = 7.54 EUR * 1.19
     *  PositionNetto              = 37.70 EUR = 7.54 EUR * 5.00
     *  PositionBrutto             = 44.85 EUR = 3.90 EUR * 5.00
     *
     *  DeliveryVAT                = 19.00 %
     *  DeliveryNetto              =  6.50 EUR = 7.74 EUR /  1.19
     *  DeliveryBrutto             =  7.74 EUR = 6.50 EUR *  1.19
     *
     *  BasketNetto EXCLUDES VOUCHER FOR B2C
     *  BasketNetto                = 44.20 EUR = 37.70 EUR + 6.50 EUR
     *                                         = PositionNetto + DeliveryNetto
     *
     *  BasketBrutto               = 46.65 EUR = (37.70 EUR * 1.19) - 5.00 EUR + 7.74 EUR
     *                                         = (PositionNetto * VAT) - Voucher + Delivery Brutto
     *                                         = PositionBrutto - Voucher + Delivery Brutto
     *
     *  TaxGroup[19.00].ValueToTax = 44.20 EUR = 37.70 EUR + 6.50 EUR
     *                                         = PositionNetto + DeliveryNetto
     *  TaxGroup[19.00].Total      =  8.39 EUR = (44.85 EUR - 37.70 EUR) + (7.74 EUR - 6.50 EUR)
     *                                         = (PositionBrutto - PositionNetto) + (DeliveryBrutto - DeliveryNetto)
     *
     *  TaxGroup[00.00].ValueToTax =    0.00 EUR [voucher is subtracted from BRUTTO]
     *  TaxGroup[00.00].Total      =   -5.00 EUR [voucher is subtracted from BRUTTO]
     */

    /** @var array<string,mixed> */
    private $orderData = [
        'oxcurrency' => 'EUR',
        'oxtotalordersum' => 46.65,
        'oxtotalbrutsum' => 46.65,
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
            'oxamount' => 5,
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

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $this->assertEquals(44.20, $createInvoiceBasketDto->netTotal);
        $this->assertEquals(46.65, $createInvoiceBasketDto->grossTotal);
        $this->assertNotNull($createInvoiceBasketDto->taxGroups);
        $this->assertNotNull($createInvoiceBasketDto->positions);
    }

    /**
     * @return void
     */
    public function test_mapping_of_article_positions()
    {
        /** @var \oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        /** @var array<mixed,\Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto> */
        $positions = $this->getCreateInvoiceBasketPositionsForArticlesByProductId($createInvoiceBasketDto);
        $this->assertEquals('162122L.03.4000', $positions['162122L.03.4000']->productId);
        $this->assertEquals('Powerback Slim Fit 4000 mAh EXPRESS', $positions['162122L.03.4000']->productName);
        $this->assertEquals(5, $positions['162122L.03.4000']->quantity);
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
        /** @var \oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $createInvoiceBasketPositionForVoucher = $this->getCreateInvoiceBasketPositionForVoucher($createInvoiceBasketDto);
        $this->assertEquals('oxvoucherdiscount', $createInvoiceBasketPositionForVoucher->productId);
        $this->assertEquals('Voucher', $createInvoiceBasketPositionForVoucher->productName);
        $this->assertEquals(1, $createInvoiceBasketPositionForVoucher->quantity);
        $this->assertEquals(-5, $createInvoiceBasketPositionForVoucher->grossPositionTotal);
        $this->assertEquals(0, $createInvoiceBasketPositionForVoucher->netPositionTotal);
        $this->assertEquals(0, $createInvoiceBasketPositionForVoucher->taxPercent);
        $this->assertEquals(0, $createInvoiceBasketPositionForVoucher->netPricePerUnit);
        $this->assertEquals(-5, $createInvoiceBasketPositionForVoucher->grossPricePerUnit);
    }

    /**
     * @return void
     */
    public function test_textrate_of_voucher_position_is_zero()
    {
        /** @var \oxOrder&MockObject */
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
        /** @var \oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $taxGroups = $this->getCreateInvoiceTaxGroupByTaxPercent($createInvoiceBasketDto);

        $this->assertCount(2, $taxGroups);

        $this->assertEquals(0, $taxGroups[0]->valueToTax);
        $this->assertEquals(-5, $taxGroups[0]->total);

        $this->assertEquals(44.20, $taxGroups[19]->valueToTax);
        $this->assertEquals(8.39, $taxGroups[19]->total);
    }
}
