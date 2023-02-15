<?php

use Axytos\KaufAufRechnung_OXID5\Extend\ServiceContainer;
use Axytos\KaufAufRechnung_OXID5\OrderSync\OrderSyncCronJob;

class AxytosMaintenance extends AxytosMaintenance_parent
{
    use ServiceContainer;

    /**
     * @return void
     */
    public function execute()
    {
        parent::execute();

        /** @var OrderSyncCronJob */
        $orderSyncCronJob = $this->getServiceFromContainer(OrderSyncCronJob::class);
        $orderSyncCronJob->execute();
    }
}
