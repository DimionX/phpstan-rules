<?php

namespace Tests\Rule\Packages\DevPackageRule;

uses(DevPackageRuleTestCase::class)->in(__DIR__);

test('dev package rule - valid with empty packages', function () {
    /** @var DevPackageRuleTestCase $this */

    $this->analyse([__DIR__ . '/Fixtures/Valid/ValidDevPackage.php'], []);
});


test('dev package rule - invalid with empty packages', function () {
    /** @var DevPackageRuleTestCase $this */

    $onlyDevPackage = "dimionx/only-dev-package";

    // useCase
    $this->addError($onlyDevPackage, 6);
    $this->addError($onlyDevPackage, 8);

    // newCase
    $this->addError($onlyDevPackage, 14);
    $this->addError($onlyDevPackage, 16);

    // staticCallCase
    $this->addError($onlyDevPackage, 21);
    $this->addError($onlyDevPackage, 23);

    // classConstFetchCase
    $this->addError($onlyDevPackage, 28);
    $this->addError($onlyDevPackage, 30);

    // instanceOfCase
    $this->addError($onlyDevPackage, 36);
    $this->addError($onlyDevPackage, 38);

    // function call
    $this->addError($onlyDevPackage, 43);

    // const
    $this->addError($onlyDevPackage, 48);

    $this->analyse(
        files: [__DIR__ . '/Fixtures/Invalid/InvalidDevPackage.php'],
        expectedErrors: $this->errors,
    );
});
