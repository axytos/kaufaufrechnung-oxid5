<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests\Unit\DataMapping;

use oxOrder;
use oxOrderArticle;
use oxList;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class BasketDtoMappingTestCase extends TestCase
{
    const PRODUCTID_FOR_SHIPPING_POSITION = '0';
    const PRODUCTID_FOR_VOUCHER_POSITION = 'oxvoucherdiscount';

    // Basket

    /**
     * @param \Axytos\ECommerce\DataTransferObjects\BasketDto $basketDto
     * @return array<mixed,\Axytos\ECommerce\DataTransferObjects\BasketPositionDto>
     */
    protected function getBasketPositionsForArticlesByProductId($basketDto)
    {
        $excludedProductIds = [
            self::PRODUCTID_FOR_SHIPPING_POSITION,
            self::PRODUCTID_FOR_VOUCHER_POSITION
        ];
        return array_reduce($basketDto->positions->getElements(), function ($result, $position) use ($excludedProductIds) {
            if (!in_array($position->productId, $excludedProductIds, true)) {
                $result[$position->productId] = $position;
            }
            return $result;
        }, []);
    }

    /**
     * @param \Axytos\ECommerce\DataTransferObjects\BasketDto $basketDto
     * @return \Axytos\ECommerce\DataTransferObjects\BasketPositionDto
     */
    protected function getBasketPositionForShipping($basketDto)
    {
        $positions = $this->getBasketPositionsByProductId($basketDto);
        return $positions[self::PRODUCTID_FOR_SHIPPING_POSITION];
    }

    /**
     * @param \Axytos\ECommerce\DataTransferObjects\BasketDto $basketDto
     * @return \Axytos\ECommerce\DataTransferObjects\BasketPositionDto
     */
    protected function getBasketPositionForVoucher($basketDto)
    {
        $positions = $this->getBasketPositionsByProductId($basketDto);
        return $positions[self::PRODUCTID_FOR_VOUCHER_POSITION];
    }

    /**
     * @param \Axytos\ECommerce\DataTransferObjects\BasketDto $basketDto
     * @return array<mixed,\Axytos\ECommerce\DataTransferObjects\BasketPositionDto>
     */
    private function getBasketPositionsByProductId($basketDto)
    {
        return array_reduce($basketDto->positions->getElements(), function ($result, $position) {
            $result[$position->productId] = $position;
            return $result;
        }, []);
    }

    //=========================================================================================
    // CreateInvoiceBasket

    /**
     * @param \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto $createInvoiceBasketDto
     * @return array<mixed,\Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto>
     */
    protected function getCreateInvoiceBasketPositionsForArticlesByProductId($createInvoiceBasketDto)
    {
        $excludedProductIds = [
            self::PRODUCTID_FOR_SHIPPING_POSITION,
            self::PRODUCTID_FOR_VOUCHER_POSITION
        ];
        return array_reduce($createInvoiceBasketDto->positions->getElements(), function ($result, $position) use ($excludedProductIds) {
            if (!in_array($position->productId, $excludedProductIds, true)) {
                $result[$position->productId] = $position;
            }
            return $result;
        }, []);
    }

    /**
     * @param \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto $createInvoiceBasketDto
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto
     */
    protected function getCreateInvoiceBasketPositionForShipping($createInvoiceBasketDto)
    {
        $positions = $this->getCreateInvoiceBasketPositionsByProductId($createInvoiceBasketDto);
        return $positions[self::PRODUCTID_FOR_SHIPPING_POSITION];
    }

    /**
     * @param \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto $createInvoiceBasketDto
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto
     */
    protected function getCreateInvoiceBasketPositionForVoucher($createInvoiceBasketDto)
    {
        $positions = $this->getCreateInvoiceBasketPositionsByProductId($createInvoiceBasketDto);
        return $positions[self::PRODUCTID_FOR_VOUCHER_POSITION];
    }

    /**
     * @param \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto $createInvoiceBasketDto
     * @return array<mixed,\Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketPositionDto>
     */
    private function getCreateInvoiceBasketPositionsByProductId($createInvoiceBasketDto)
    {
        return array_reduce($createInvoiceBasketDto->positions->getElements(), function ($result, $position) {
            $result[$position->productId] = $position;
            return $result;
        }, []);
    }

    /**
     * @param \Axytos\ECommerce\DataTransferObjects\CreateInvoiceBasketDto $createInvoiceBasketDto
     * @return array<mixed,\Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto>
     */
    protected function getCreateInvoiceTaxGroupByTaxPercent($createInvoiceBasketDto)
    {
        return array_reduce($createInvoiceBasketDto->taxGroups->getElements(), function ($result, $position) {
            $result[$position->taxPercent] = $position;
            return $result;
        }, []);
    }

    //=========================================================================================
    // Mockking

    /**
     * @param array<string,mixed> $fieldData
     * @param array<array<string,mixed>> $articlesData
     * @return oxOrder&MockObject
     */
    protected function createOrderMock($fieldData, $articlesData)
    {
        /** @var array<oxOrderArticle&MockObject> */
        $orderArticleMocks = array_map([$this, 'createOrderArticleMock'], $articlesData);
        /** @var oxOrder&MockObject */
        $mock = $this->createMock(oxOrder::class);
        $mock->method('getFieldData')->willReturnCallback(function ($fieldName) use ($fieldData) {
            if (!array_key_exists($fieldName, $fieldData)) {
                return null;
            }
            return $fieldData[$fieldName];
        });
        $mock->method('getOrderArticles')->willReturnCallback(function () use ($orderArticleMocks) {
            $list = $this->createMock(oxList::class);
            $list->method('getArray')->willReturn($orderArticleMocks);
            return $list;
        });
        return $mock;
    }

    /**
     * @param array<string,mixed> $fieldData
     * @return oxOrderArticle&MockObject
     */
    protected function createOrderArticleMock($fieldData)
    {
        /** @var oxOrderArticle&MockObject */
        $mock = $this->createMock(oxOrderArticle::class);
        $mock->method('getFieldData')->willReturnCallback(function ($fieldName) use ($fieldData) {
            if (!array_key_exists($fieldName, $fieldData)) {
                return null;
            }
            return $fieldData[$fieldName];
        });
        return $mock;
    }
}
