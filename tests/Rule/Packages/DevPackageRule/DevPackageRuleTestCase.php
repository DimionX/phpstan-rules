<?php

namespace Tests\Rule\Packages\DevPackageRule;

use DimionX\PHPStan\Rule\Packages\DevPackageRule;
use PHPStan\DependencyInjection\MissingServiceException;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<DevPackageRule>
 */
class DevPackageRuleTestCase extends RuleTestCase
{
    protected array $errors = [];

    /**
     * @throws MissingServiceException
     */
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(DevPackageRule::class);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../../../extension.neon',  # base config
            __DIR__ . '/Config/phpstan.neon',         # params config
        ];
    }

    public function addError(string $className, int $line): void
    {
        $this->errors[] = [DevPackageRule::buildMessage($className), $line];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
