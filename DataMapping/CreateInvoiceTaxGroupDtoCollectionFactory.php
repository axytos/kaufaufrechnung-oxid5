<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDto;
use Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDtoCollection;
use oxList;
use oxOrder;

class CreateInvoiceTaxGroupDtoCollectionFactory
{
    /**
     * @var \Axytos\KaufAufRechnung_OXID5\DataMapping\CreateInvoiceTaxGroupDtoFactory
     */
    private $createInvoiceTaxGroupDtoFactory;

    public function __construct(CreateInvoiceTaxGroupDtoFactory $createInvoiceTaxGroupDtoFactory)
    {
        $this->createInvoiceTaxGroupDtoFactory = $createInvoiceTaxGroupDtoFactory;
    }

    /**
     * @param \oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\CreateInvoiceTaxGroupDtoCollection
     */
    public function create($order)
    {
        /** @var oxList */
        $orderArticles = $order->getOrderArticles();

        $positionTaxValues = array_map([$this->createInvoiceTaxGroupDtoFactory, 'create'], $orderArticles->getArray());

        $voucherTaxGroup = $this->createInvoiceTaxGroupDtoFactory->createVoucherPosition($order);
        if (!is_null($voucherTaxGroup)) {
            $positionTaxValues[] = $voucherTaxGroup;
        }

        $positionTaxValues[] = $this->createInvoiceTaxGroupDtoFactory->createShippingPosition($order);

        $taxGroups = array_values(
            array_reduce(
                $positionTaxValues,
                function (array $agg, CreateInvoiceTaxGroupDto $cur) {
                    if (array_key_exists("$cur->taxPercent", $agg)) {
                        $agg["$cur->taxPercent"]->total += $cur->total;
                        $agg["$cur->taxPercent"]->valueToTax += $cur->valueToTax;
                    } else {
                        $agg["$cur->taxPercent"] = $cur;
                    }
                    return $agg;
                },
                []
            )
        );
        return new CreateInvoiceTaxGroupDtoCollection(...$taxGroups);
    }
}
