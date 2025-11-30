<?php

namespace DimionX\DevProd;

class DevProdClass
{
    public const VALUE = 42;

    public static function new(): self
    {
        return new self();
    }
}
