<?php

namespace DimionX\OnlyProd;

use DimionX\OnlyDev\SomeDevClass;

trait TraitWithDevImport
{
    public function doSomething(): mixed
    {
        return SomeDevClass::VALUE;
    }
}
