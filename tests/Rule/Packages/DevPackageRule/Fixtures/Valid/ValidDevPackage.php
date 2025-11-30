<?php

namespace Tests\Rule\Packages\DevPackageRule\Fixtures\Valid;

use DimionX\DevProd\DevProdClass;

use function DimionX\DevProd\devProdFunction;

use DimionX\OnlyProd\OnlyProdClass;

use function DimionX\OnlyProd\onlyProdFunction;

class ValidDevPackage
{
    public function newCase(): void
    {
        $var = new \DimionX\OnlyProd\OnlyProdClass();
        $var = new OnlyProdClass();
        $var = new DevProdClass();
    }

    public function staticCallCase(): void
    {
        $var = \DimionX\OnlyProd\OnlyProdClass::new();
        $var = OnlyProdClass::new();
        $var = DevProdClass::new();
    }

    public function classConstFetchCase(): void
    {
        $var = \DimionX\OnlyProd\OnlyProdClass::class;
        $var = OnlyProdClass::class;
        $var = DevProdClass::class;
    }

    public function instanceOfCase(): void
    {
        $var = self::class;
        $result = $var instanceof \DimionX\OnlyProd\OnlyProdClass;
        $result = $var instanceof OnlyProdClass;
        $result = $var instanceof DevProdClass;
    }

    public function functionsCase(): void
    {
        onlyProdFunction();
        devProdFunction();
    }

    public function constCase(): void
    {
        $var = OnlyProdClass::VALUE;
        $var = DevProdClass::VALUE;
    }
}
