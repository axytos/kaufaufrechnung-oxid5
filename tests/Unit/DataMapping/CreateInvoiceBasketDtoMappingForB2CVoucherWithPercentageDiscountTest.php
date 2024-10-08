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
class CreateInvoiceBasketDtoMappingForB2CVoucherWithPercentageDiscountTest extends BasketDtoMappingTestCase
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
     *  Voucher                    =  10.00 %
     *
     *  PositionVAT                =  19.00 %
     *  PositionAmount             =  50.00
     *  PositionUnitNetto          =   8.71 EUR = 10.36 EUR /  1.19
     *  PositionUnitBrutto         =  10.36 EUR =  8.71 EUR *  1.19
     *  PositionNetto              = 435.50 EUR =  8.71 EUR * 50.00
     *  PositionBrutto             = 519.00 EUR = 10.36 EUR * 50.00
     *
     *  DeliveryVAT                =  19.00 %
     *  DeliveryNetto              =   6.50 EUR = 7.74 EUR /  1.19
     *  DeliveryBrutto             =   7.74 EUR = 6.50 EUR *  1.19
     *
     *  BasketNetto EXCLUDES VOUCHER FOR B2C
     *  BasketNetto                = 442.00 EUR = 435.50 EUR + 6.50 EUR
     *                                          = PositionNetto + DeliveryNetto
     *
     *  BasketBrutto               = 474.84 EUR = 519.00 EUR * (1 - 0.10) + 7.74 EUR
     *                                          = PositionBrutto * (1 - Voucher) + Delivery Brutto
     *
     *  TaxGroup[19.00].ValueToTax = 442.00 EUR = 435.50 EUR + 6.50 EUR
     *                                          = PositionNetto + DeliveryNetto
     *  TaxGroup[19.00].Total      =  84.74 EUR = (519.00 EUR - 435.50 EUR) + (7.74 EUR - 6.50 EUR)
     *                                          = (PositionBrutto - PositionNetto) + (DeliveryBrutto - DeliveryNetto)
     *
     *  TaxGroup[00.00].ValueToTax =   0.00 EUR [voucher is subtracted from BRUTTO]
     *  TaxGroup[00.00].Total      = -51.90 EUR [voucher is subtracted from BRUTTO]
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
        /** @var \oxOrder&MockObject */
        $order = $this->createOrderMock($this->orderData, $this->articleData);

        /** @var \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto */
        $createInvoiceBasketDto = $this->sut->create($order);

        $this->assertEquals(442.00, $createInvoiceBasketDto->netTotal);
        $this->assertEquals(474.84, $createInvoiceBasketDto->grossTotal);
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
        $this->assertEquals(-51.90, $createInvoiceBasketPositionForVoucher->grossPositionTotal);
        $this->assertEquals(0, $createInvoiceBasketPositionForVoucher->netPositionTotal);
        $this->assertEquals(0, $createInvoiceBasketPositionForVoucher->taxPercent);
        $this->assertEquals(0, $createInvoiceBasketPositionForVoucher->netPricePerUnit);
        $this->assertEquals(-51.90, $createInvoiceBasketPositionForVoucher->grossPricePerUnit);
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
        $this->assertEquals(-51.90, $taxGroups[0]->total);

        $this->assertEquals(442.0, $taxGroups[19]->valueToTax);
        $this->assertEquals(84.74, $taxGroups[19]->total);
    }
}
