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
use PhpParser\Node\Stmt\TraitUse;
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

    /** @var array<string, true> */
    private array $checkedFiles = [];

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
     * @return IdentifierRuleError[]
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        // Отдельная обработка TraitUse: перебираем все имена трейтов
        if ($node instanceof TraitUse) {
            foreach ($node->traits as $trait) {
                $name = $trait->toString();
                $errors = array_merge($errors, $this->checkSymbolAndItsFile($scope, $node, $name));
            }

            return $errors;
        }

        $name = $this->parseName($node);
        if ($name !== null) {
            $errors = $this->checkSymbolAndItsFile($scope, $node, $name);
        }

        return $errors;
    }

    /**
     * Проверяет, не является ли сам символ dev-зависимостью,
     * а затем анализирует файл этого символа на наличие dev-импортов.
     */
    private function checkSymbolAndItsFile(Scope $scope, Node $originalNode, string $name): array
    {
        $errors = [];

        // 1. Является ли сам символ dev-пакетом?
        $package = $this->parseDevPackage($scope, $name);
        if ($package !== null) {
            $errors[] = RuleErrorBuilder::message(static::buildMessage($package))
                ->identifier('dev.packageUsedInProductionRule')
                ->build();
        }

        // 2. Получаем путь к файлу, где определён символ
        $filePath = $this->getSymbolFilePath($scope, $name);

        if ($filePath !== null && !isset($this->checkedFiles[$filePath])) {
            $this->checkedFiles[$filePath] = true;
            // Проверяем содержимое файла на импорты dev-пакетов
            $errors = array_merge($errors, $this->checkFileForDevImports($filePath, $scope, $originalNode));
        }

        return $errors;
    }

    /**
     * Возвращает абсолютный путь к файлу класса/трейта/интерфейса/функции.
     */
    private function getSymbolFilePath(Scope $scope, string $name): ?string
    {
        try {
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

            return realpath($path) ?: $path;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Читает файл и ищет в нём use-импорты классов, проверяя,
     * не относятся ли они к dev-пакетам.
     *
     * Поддерживается обычный синтаксис: use Namespace\Class; (с алиасом или без).
     * Групповые импорты (use Vendor\{A, B}) не обрабатываются.
     */
    private function checkFileForDevImports(string $filePath, Scope $scope, Node $originalNode): array
    {
        if (!is_file($filePath)) {
            return [];
        }

        $contents = file_get_contents($filePath);
        $pattern = '/^\s*use\s+([\\\\\\w]+)(?:\s+as\s+\w+)?\s*;/m';
        if (!preg_match_all($pattern, $contents, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $errors = [];
        foreach ($matches as $match) {
            $className = $match[1];
            $package = $this->parseDevPackage($scope, $className);
            if ($package !== null) {
                $errors[] = RuleErrorBuilder::message(
                    sprintf(
                        'File "%s" (used by production code) imports dev class %s.',
                        $this->getRelativePath($filePath),
                        $className
                    )
                )
                    ->identifier('dev.packageUsedInProductionRule')
                    ->file($scope->getFile())
                    ->line($originalNode->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    private function getRelativePath(string $absolutePath): string
    {
        $cwd = getcwd() . '/';
        if (str_starts_with($absolutePath, $cwd)) {
            return substr($absolutePath, strlen($cwd));
        }

        return $absolutePath;
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

            if ($classReflection->isInternal()) {
                return null;
            }

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

        if ($package === 'phpstan/phpstan') {
            return null;
        }

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
