<?php

namespace DimionX\PHPStan\Rule\Packages;

use DimionX\PHPStan\Factory\ComposerLockFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\UseItem;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

class DevPackageRule implements Rule
{
    protected array $onlyDevClasses;

    /**
     * @throws ShouldNotHappenException
     */
    public function __construct(
        ComposerLockFactory $composerLockFactory,
        protected array $autoloadCheckTypes = ['psr-4'],
    ) {
        $packages = $composerLockFactory->read();

        $dev = $this->getAllClassNames($packages['packages-dev']);
        $prod = $this->getAllClassNames($packages['packages']);

        $this->onlyDevClasses = array_diff_key($dev, $prod);
    }

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @param UseItem $node
     * @param Scope $scope
     * @return IdentifierRuleError[]
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $className = $this->parseClass($node);
        if ($className && $this->isOnlyDevClass($className)) {
            return [
                RuleErrorBuilder::message(static::buildMessage($className))
                    ->identifier('dev.packageUsedInProductionRule')
                    ->build(),
            ];
        }

        return [];
    }

    public static function buildMessage(string $className): string
    {
        return "Usage of dev package class '$className' in production code is prohibited.";
    }

    protected function parseClass(Node $node): ?string
    {
        return match (get_class($node)) {
            UseItem::class => $node->name->toString(),      # use DevPackage\ClassName;
            New_::class => $node->class->name,              # $var = new \DevPackage\ClassName();
            StaticCall::class => $node->class->name,        # $var = \DevPackage\ClassName::new();
            ClassConstFetch::class => $node->class->name,   # $var = \DevPackage\ClassName::class;
            Instanceof_::class => $node->class->name,       # $var instanceof \DevPackage\ClassName
            default => null,
        };
    }

    protected function isOnlyDevClass(string $className): bool
    {
        foreach ($this->onlyDevClasses as $namespace) {
            if ($className === $namespace || str_starts_with($className, $namespace . '\\')) {
                return true;
            }
        }

        return false;
    }

    protected function getAllClassNames(array $packages): array
    {
        $names = [];
        foreach ($packages as $package) {
            $autoloadSections = array_merge(
                $package['autoload'] ?? [],
                $package['autoload-dev'] ?? []
            );

            foreach ($autoloadSections as $type => $mappings) {
                if (!in_array($type, $this->autoloadCheckTypes)) {
                    continue;
                }

                /** @var string $className */
                foreach ($mappings as $className => $path) {
                    $className = rtrim($className, '\\');
                    $names[$className] = $className;
                }
            }
        }

        return $names;
    }
}
