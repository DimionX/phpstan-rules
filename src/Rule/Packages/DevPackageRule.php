<?php

namespace DimionX\PHPStan\Rule\Packages;

use DimionX\PHPStan\Factory\ComposerLockFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name as NodeName;
use PhpParser\Node\UseItem;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;

class DevPackageRule implements Rule
{
    protected array $onlyDevPackages;

    /**
     * @throws ShouldNotHappenException
     */
    public function __construct(
        private ReflectionProvider $reflectionProvider,
        ComposerLockFactory $composerLockFactory,
    ) {
        $packages = $composerLockFactory->read();

        $dev = $this->getNames($packages['packages-dev']);
        $prod = $this->getNames($packages['packages']);

        $this->onlyDevPackages = array_diff_key($dev, $prod);
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
        $name = $this->parseName($node);
        if (!$name) {
            return [];
        }

        $package = $this->parseDevPackage($scope, $name);
        if ($package) {
            return [
                RuleErrorBuilder::message(static::buildMessage($package))
                    ->identifier('dev.packageUsedInProductionRule')
                    ->build(),
            ];
        }

        return [];
    }

    public static function buildMessage(string $packageName): string
    {
        return "Usage of dev package '$packageName' in production code is prohibited.";
    }

    protected function parseName(Node $node): ?string
    {
        return match (get_class($node)) {
            UseItem::class => $node->name->name,                   # use DevPackage\ClassName;
            New_::class => $node->class->name,                     # $var = new \DevPackage\ClassName();
            StaticCall::class => $node->class->name,               # $var = \DevPackage\ClassName::new();
            ClassConstFetch::class => $node->class->name,          # $var = \DevPackage\ClassName::class;
            Instanceof_::class => $node->class->name,              # $var instanceof \DevPackage\ClassName
            FuncCall::class => $this->resolveFuncCallName($node),  # postJson()
            default => null,
        };
    }

    protected function resolveFuncCallName(FuncCall $node): ?string
    {
        return $node->name instanceof NodeName ? $node->name->toString() : null;
    }

    protected function parseDevPackage(Scope $scope, string $name): ?string
    {
        if ($this->isClass($name)) {
            $classReflection = $this->reflectionProvider->getClass($name);
            $path = $classReflection->getFileName();
        } elseif ($this->isFunction($scope, $name)) {
            $nodeName = new NodeName($name);
            $functionReflection = $this->reflectionProvider->getFunction($nodeName, $scope);
            $path = $functionReflection->getFileName();
        } else {
            return null;
        }

        $path = realpath($path) ?: $path;
        if (!preg_match('#phpstan.phar/vendor/([^/]+/[^/]+)/#', $path, $matches)) {
            if (!preg_match('#vendor/([^/]+/[^/]+)/#', $path, $matches)) {
                return null;
            }
        }

        $package = $matches[1];
        if (array_key_exists($package, $this->onlyDevPackages)) {
            return $package;
        }

        return null;
    }

    protected function isClass(string $class): bool
    {
        return class_exists($class, true)
            || interface_exists($class, true)
            || trait_exists($class, true);
    }

    protected function isFunction(Scope $scope, string $name): bool
    {
        return $this->reflectionProvider->hasFunction(nameNode: new NodeName($name), namespaceAnswerer: $scope);
    }

    protected function getNames(array $packages): array
    {
        $names = array_column($packages, 'name');

        return array_combine($names, $names);
    }
}
