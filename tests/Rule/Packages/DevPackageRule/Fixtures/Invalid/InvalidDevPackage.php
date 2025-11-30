<?php

namespace Tests\Rule\Packages\DevPackageRule\Fixtures\Invalid;

use DimionX\OnlyDev\Dev;

class Example
{
    public function newCase(): void
    {
        $var = new Dev();
        $var = new \DimionX\OnlyDev\DevSecond();
    }

    public function staticCallCase(): void
    {
        $var = Dev::new();
        $var = \DimionX\OnlyDev\DevSecond::new();
    }

    public function classConstFetchCase(): void
    {
        $var = Dev::class;
        $var = \DimionX\OnlyDev\DevSecond::class;
    }

    public function instanceOfCase(): void
    {
        $var = self::class;
        $result = $var instanceof Dev;
        $result = $var instanceof \DimionX\OnlyDev\DevSecond;
    }
}
