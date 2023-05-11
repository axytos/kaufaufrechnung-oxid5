<?php

namespace Axytos\KaufAufRechnung_OXID5\OrderSync;

use Axytos\ECommerce\OrderSync\OrderHashCalculator;
use Axytos\ECommerce\OrderSync\ShopSystemOrderInterface;
use Axytos\KaufAufRechnung_OXID5\Core\InvoiceOrderContextFactory;
use oxDb;
use oxField;
use oxOrder;
use oxRegistry;
use oxUtilsDate;

class ShopSystemOrder implements ShopSystemOrderInterface
{
    /**
     * @var oxOrder
     */
    private $order;

    /**
     * @var InvoiceOrderContextFactory
     */
    private $invoiceOrderContextFactory;

    /**
     * @var OrderHashCalculator
     */
    private $orderHashCalculator;

    public function __construct(
        oxOrder $order,
        InvoiceOrderContextFactory $invoiceOrderContextFactory,
        OrderHashCalculator $orderHashCalculator
    ) {
        $this->order = $order;
        $this->invoiceOrderContextFactory = $invoiceOrderContextFactory;
        $this->orderHashCalculator = $orderHashCalculator;
    }

    /**
     * @return string|int|null
     */
    public function getOrderNumber()
    {
        /** @var int */
        return $this->order->getFieldData('oxordernr');
    }

    // Transactions

    /**
     * @return void
     */
    public function beginPersistenceTransaction()
    {
        oxDb::getDb()->startTransaction();
    }

    /**
     * @return void
     */
    public function commitPersistenceTransaction()
    {
        oxDb::getDb()->commitTransaction();
    }

    /**
     * @return void
     */
    public function rollbackPersistenceTransaction()
    {
        oxDb::getDb()->rollbackTransaction();
    }


    // CreateInvoice

    /**
     * @return bool
     */
    public function hasCreateInvoiceReported()
    {
        /** @phpstan-ignore-next-line */
        return $this->order->getFieldData("axytoskaufaufrechnungcreateinvoicereported");
    }

    /**
     * @return void
     */
    public function saveHasCreateInvoiceReported()
    {
        /** @phpstan-ignore-next-line */
        $this->order->oxorder__axytoskaufaufrechnungcreateinvoicereported = new oxField(1);
        $this->order->save();
    }

    /**
     * @return bool
     */
    public function hasBeenInvoiced()
    {
        return strval($this->order->getFieldData("oxbillnr")) !== '';
    }

    /**
     * @return \Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface
     */
    public function getCreateInvoiceReportData()
    {
        return $this->invoiceOrderContextFactory->getInvoiceOrderContext($this->order);
    }

    // Cancel

    /**
     * @return bool
     */
    public function hasCancelReported()
    {
        /** @phpstan-ignore-next-line */
        return $this->order->getFieldData("axytoskaufaufrechnungcancelreported");
    }

    /**
     * @return void
     */
    public function saveHasCancelReported()
    {
        /** @phpstan-ignore-next-line */
        $this->order->oxorder__axytoskaufaufrechnungcancelreported = new oxField(1);
        $this->order->save();
    }

    /**
     * @return bool
     */
    public function hasBeenCanceled()
    {
        return boolval($this->order->getFieldData("oxstorno"));
    }

    /**
     * @return \Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface
     */
    public function getCancelReportData()
    {
        return $this->invoiceOrderContextFactory->getInvoiceOrderContext($this->order);
    }

    // Refund

    /**
     * @return bool
     */
    public function hasRefundReported()
    {
        return false;
    }

    /**
     * @return void
     */
    public function saveHasRefundReported()
    {
    }

    /**
     * @return bool
     */
    public function hasBeenRefunded()
    {
        return false;
    }

    /**
     * @return \Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface
     */
    public function getRefundReportData()
    {
        return $this->invoiceOrderContextFactory->getInvoiceOrderContext($this->order);
    }

    // Shipping

    /**
     * @return bool
     */
    public function hasShippingReported()
    {
        /** @phpstan-ignore-next-line */
        return $this->order->getFieldData("axytoskaufaufrechnungshippingreported");
    }

    /**
     * @return void
     */
    public function saveHasShippingReported()
    {
        /** @phpstan-ignore-next-line */
        $this->order->oxorder__axytoskaufaufrechnungshippingreported = new oxField(1);
        $this->order->save();
    }

    /**
     * @return bool
     */
    public function hasBeenShipped()
    {
        /** @var oxUtilsDate */
        $dateUtils = oxRegistry::get(oxUtilsDate::class);

        /** @var string */
        $sendDateRaw = $this->order->getFieldData("oxsenddate");
        $sendDate = $dateUtils->formatDBDate($sendDateRaw, true);

        return $sendDate !== "0000-00-00 00:00:00" &&
            $sendDate !== "-" &&
            $sendDate !== '';
    }

    /**
     * @return \Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface
     */
    public function getShippingReportData()
    {
        return $this->invoiceOrderContextFactory->getInvoiceOrderContext($this->order);
    }

    // Tracking Information

    /**
     * @return bool
     */
    public function hasNewTrackingInformation()
    {
        /** @var string */
        $trackCode = $this->order->getFieldData("oxtrackcode");
        /** @var string */
        $reportedTrackingCode = $this->order->getFieldData("axytoskaufaufrechnungreportedtrackingcode");
        return $trackCode !== $reportedTrackingCode;
    }

    /**
     * @return void
     */
    public function saveNewTrackingInformation()
    {
        /** @var string */
        $trackCode = $this->order->getFieldData("oxtrackcode");
        /** @phpstan-ignore-next-line */
        $this->order->oxorder__axytoskaufaufrechnungreportedtrackingcode = new oxField($trackCode);
        $this->order->save();
    }

    /**
     * @return \Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface
     */
    public function getNewTrackingInformationReportData()
    {
        return $this->invoiceOrderContextFactory->getInvoiceOrderContext($this->order);
    }

    // Order Basket Updates

    /**
     * @return bool
     */
    public function hasBasketUpdates()
    {
        /** @var string */
        $oldHash = $this->order->getFieldData("axytoskaufaufrechnungorderbaskethash");
        $newHash = $this->calculateOrderBasketHash();
        return $newHash !== $oldHash;
    }

    /**
     * @return void
     */
    public function saveBasketUpdatesReported()
    {
        $orderHash = $this->calculateOrderBasketHash();
        /** @phpstan-ignore-next-line */
        $this->order->oxorder__axytoskaufaufrechnungorderbaskethash = new oxField($orderHash);
        $this->order->save();
    }

    /**
     * @return \Axytos\ECommerce\Clients\Invoice\InvoiceOrderContextInterface
     */
    public function getBasketUpdateReportData()
    {
        return $this->invoiceOrderContextFactory->getInvoiceOrderContext($this->order);
    }

    /**
     * @return string
     */
    private function calculateOrderBasketHash()
    {
        $orderContext = $this->invoiceOrderContextFactory->getInvoiceOrderContext($this->order);
        return $this->orderHashCalculator->computeBasketHash($orderContext);
    }
}
