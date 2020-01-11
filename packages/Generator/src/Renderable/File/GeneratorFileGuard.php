<?php

declare(strict_types=1);

namespace Symplify\Statie\Generator\Renderable\File;

use Symplify\SmartFileSystem\SmartFileInfo;
use Symplify\Statie\Generator\Exception\Configuration\GeneratorException;

final class GeneratorFileGuard
{
    /**
     * @var int[][]
     */
    private $idsByAbstractGeneratorFileClass = [];

    public function ensureIsAbstractGeneratorFile(string $class): void
    {
        if (is_a($class, AbstractGeneratorFile::class, true)) {
            return;
        }

        throw new GeneratorException(sprintf('"%s" must inherit from "%s"', $class, AbstractGeneratorFile::class));
    }

    public function ensureIdIsUnique(int $id, string $className, SmartFileInfo $smartFileInfo): void
    {
        if (! isset($this->idsByAbstractGeneratorFileClass[$className])) {
            $this->idsByAbstractGeneratorFileClass[$className][] = $id;
            return;
        }

        if (! in_array($id, $this->idsByAbstractGeneratorFileClass[$className], true)) {
            $this->idsByAbstractGeneratorFileClass[$className][] = $id;
            return;
        }

        throw new GeneratorException(sprintf(
            'Id "%d" was already set for "%s" class. Pick an another one for "%s" file.',
            $id,
            $className,
            $smartFileInfo->getRealPath()
        ));
    }

    /**
     * @param mixed[]|null $match
     */
    public function ensureIdIsSet(SmartFileInfo $smartFileInfo, ?array $match): void
    {
        if (isset($match['id'])) {
            return;
        }

        throw new GeneratorException(sprintf(
            'File "%s" must have "id: [0-9]+" in the header in --- blocks.',
            $smartFileInfo->getRealPath()
        ));
    }
}
