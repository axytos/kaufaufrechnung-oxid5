<?php

namespace Axytos\KaufAufRechnung_OXID5\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class BaseTest extends TestCase
{
    /**
     * @return void
     */
    public function test_oxi_d_class_can_be_autoloaded()
    {
        $address = $this->createMock(\oxAddress::class);

        $this->assertInstanceOf(\oxBase::class, $address);
    }
}
