<?php

namespace Tests\Rule\Packages\DevPackageRule\Fixtures\Valid;

use DimionX\DevProduction\DevProd;
use DimionX\OnlyDevNotAssert;

class Example
{
    public function newCase(): void
    {
        $var = new \DimionX\OnlyProduction\Production();
        $var = new DevProd();
        $var = new OnlyDevNotAssert();
    }

    public function staticCallCase(): void
    {
        $var = \DimionX\OnlyProduction\Production::new();
        $var = DevProd::new();
        $var = OnlyDevNotAssert::new();
    }

    public function classConstFetchCase(): void
    {
        $var = \DimionX\OnlyProduction\Production::class;
        $var = DevProd::class;
        $var = OnlyDevNotAssert::class;
    }

    public function instanceOfCase(): void
    {
        $var = self::class;
        $result = $var instanceof \DimionX\OnlyProduction\Production;
        $result = $var instanceof DevProd;
        $result = $var instanceof OnlyDevNotAssert;
    }
}
