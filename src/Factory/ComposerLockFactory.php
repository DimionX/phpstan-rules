<?php

namespace DimionX\PHPStan\Factory;

use PHPStan\ShouldNotHappenException;

class ComposerLockFactory
{
    private ?array $data = null;

    public function __construct(private string $pathToComposerLockFile = 'composer.lock')
    {
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function read(): array
    {
        if (isset($this->data)) {
            return $this->data;
        }

        if (!file_exists($this->pathToComposerLockFile)) {
            throw new ShouldNotHappenException(
                message: "Composer lock file not found in '{$this->pathToComposerLockFile}'"
            );
        }

        $data = file_get_contents($this->pathToComposerLockFile);
        if ($data === false) {
            throw new ShouldNotHappenException("Error reading '{$this->pathToComposerLockFile}'");
        }

        $this->data = json_decode($data, true) ?? [];

        return $this->data;
    }
}
