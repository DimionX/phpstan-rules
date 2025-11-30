<?php

namespace DimionX\OnlyProd;

class OnlyProdClass
{
    public const VALUE = 42;

    public static function new(): self
    {
        return new self();
    }
}
