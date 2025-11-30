<?php

namespace Tests\Rule\Packages\DevPackageRule\Fixtures\Invalid;

use DimionX\DevProd\DevProdClass;
use DimionX\OnlyDev\OnlyDevClass;

use function DimionX\OnlyDev\onlyDevFunction;

class InvalidDevPackage
{
    public function newCase(): void
    {
        $var = new OnlyDevClass();
        $var = new DevProdClass();
        $var = new \DimionX\OnlyDev\OnlyDevClass();
    }

    public function staticCallCase(): void
    {
        $var = OnlyDevClass::new();
        $var = DevProdClass::new();
        $var = \DimionX\OnlyDev\OnlyDevClass::new();
    }

    public function classConstFetchCase(): void
    {
        $var = OnlyDevClass::class;
        $var = DevProdClass::class;
        $var = \DimionX\OnlyDev\OnlyDevClass::class;
    }

    public function instanceOfCase(): void
    {
        $var = self::class;
        $result = $var instanceof OnlyDevClass;
        $result = $var instanceof DevProdClass;
        $result = $var instanceof \DimionX\OnlyDev\OnlyDevClass;
    }

    public function functionsCase(): void
    {
        onlyDevFunction();
    }

    public function constCase(): void
    {
        $var = OnlyDevClass::VALUE;
    }
}
