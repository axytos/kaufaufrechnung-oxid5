<?php

use Axytos\KaufAufRechnung_OXID5\Extend\AxytosServiceContainer;
use Axytos\KaufAufRechnung_OXID5\OrderSync\OrderSyncCronJob;

class AxytosMaintenance extends AxytosMaintenance_parent
{
    use AxytosServiceContainer;

    /**
     * @return void
     */
    public function execute()
    {
        parent::execute();

        /** @var OrderSyncCronJob */
        $orderSyncCronJob = $this->getFromAxytosServiceContainer(OrderSyncCronJob::class);
        $orderSyncCronJob->execute();
    }
}
