<?php

namespace Tests\Rule\Packages\DevPackageRule;

uses(DevPackageRuleTestCase::class)->in(__DIR__);

test('dev package rule - valid with empty packages', function () {
    /** @var DevPackageRuleTestCase $this */

    $this->analyse([__DIR__ . '/Fixtures/Valid/ValidDevPackage.php'], []);
});


test('dev package rule - invalid with empty packages', function () {
    /** @var DevPackageRuleTestCase $this */

    $a = "DimionX\\OnlyDev\\Dev";
    $b = "DimionX\\OnlyDev\\DevSecond";

    // useCase
    $this->addError($a, 5);

    // newCase
    $this->addError($a, 11);
    $this->addError($b, 12);

    // staticCallCase
    $this->addError($a, 17);
    $this->addError($b, 18);

    // classConstFetchCase
    $this->addError($a, 23);
    $this->addError($b, 24);

    // instanceOfCase
    $this->addError($a, 30);
    $this->addError($b, 31);

    $this->analyse(
        files: [__DIR__ . '/Fixtures/Invalid/InvalidDevPackage.php'],
        expectedErrors: $this->errors,
    );
});
