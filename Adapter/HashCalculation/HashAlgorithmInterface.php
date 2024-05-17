<?php

namespace Axytos\KaufAufRechnung_OXID5\Adapter\HashCalculation;

interface HashAlgorithmInterface
{
    /**
     * @param string $input
     * @return string
     */
    public function compute($input);
}
