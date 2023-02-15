<?php

namespace Axytos\KaufAufRechnung_OXID5\DataMapping;

use Axytos\ECommerce\DataTransferObjects\CustomerDataDto;
use DateTimeImmutable;
use oxOrder;
use oxUser;

class CustomerDataDtoFactory
{
    /**
     * @param oxOrder $order
     * @return \Axytos\ECommerce\DataTransferObjects\CustomerDataDto
     */
    public function create($order)
    {
        /** @var oxUser */
        $user = $order->getOrderUser();

        $personalDataDto = new CustomerDataDto();
        /** @phpstan-ignore-next-line */
        $personalDataDto->externalCustomerId = $user->getFieldData('oxcustnr');
        if ($user->getFieldData("oxbirthdate") !== "0000-00-00") {
            /** @phpstan-ignore-next-line */
            $personalDataDto->dateOfBirth = DateTimeImmutable::createFromFormat('Y-m-d G:i:s', $user->getFieldData("oxbirthdate") . " 00:00:00");
        }
        /** @phpstan-ignore-next-line */
        $personalDataDto->email = $order->getFieldData("oxbillemail");

        return $personalDataDto;
    }
}
