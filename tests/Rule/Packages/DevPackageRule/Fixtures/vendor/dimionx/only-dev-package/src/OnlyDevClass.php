<?php

namespace DimionX\OnlyDev;

class OnlyDevClass
{
    public const VALUE = 42;

    public static function new(): self
    {
        return new self();
    }
}
