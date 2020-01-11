<?php

declare(strict_types=1);

namespace Symplify\Statie\Renderable;

use Symplify\SmartFileSystem\SmartFileInfo;
use Symplify\Statie\Configuration\StatieConfiguration;
use Symplify\Statie\Contract\Renderable\FileDecoratorInterface;
use Symplify\Statie\Generator\Configuration\GeneratorElement;
use Symplify\Statie\Generator\Renderable\File\AbstractGeneratorFile;
use Symplify\Statie\Renderable\File\AbstractFile;
use Symplify\Statie\Renderable\File\FileFactory;

final class RenderableFilesProcessor
{
    /**
     * @var FileDecoratorInterface[]
     */
    private $fileDecorators = [];

    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var StatieConfiguration
     */
    private $statieConfiguration;

    /**
     * @param FileDecoratorInterface[] $fileDecorators
     */
    public function __construct(
        FileFactory $fileFactory,
        StatieConfiguration $statieConfiguration,
        array $fileDecorators
    ) {
        $this->fileFactory = $fileFactory;
        $this->statieConfiguration = $statieConfiguration;
        $this->fileDecorators = $this->sortFileDecorators($fileDecorators);
    }

    /**
     * @param SmartFileInfo[] $fileInfos
     * @return AbstractFile[]
     */
    public function processFileInfos(array $fileInfos): array
    {
        if (count($fileInfos) === 0) {
            return [];
        }

        $files = $this->fileFactory->createFromFileInfos($fileInfos);

        foreach ($this->getFileDecorators() as $fileDecorator) {
            $files = $fileDecorator->decorateFiles($files);
        }

        return $files;
    }

    /**
     * @param AbstractGeneratorFile[] $objects
     * @return AbstractGeneratorFile[]
     */
    public function processGeneratorElementObjects(array $objects, GeneratorElement $generatorElement): array
    {
        if (count($objects) === 0) {
            return [];
        }

        foreach ($this->getFileDecorators() as $fileDecorator) {
            $objects = $fileDecorator->decorateFilesWithGeneratorElement($objects, $generatorElement);
        }

        $objectSorter = $generatorElement->getObjectSorter();

        /** @var AbstractGeneratorFile[] $objects */
        $objects = $objectSorter->sort($objects);

        $this->statieConfiguration->addOption($generatorElement->getVariableGlobal(), $objects);

        return $objects;
    }

    /**
     * @return FileDecoratorInterface[]
     */
    public function getFileDecorators(): array
    {
        return $this->fileDecorators;
    }

    /**
     * @param FileDecoratorInterface[] $fileDecorators
     * @return FileDecoratorInterface[]
     */
    private function sortFileDecorators(array $fileDecorators): array
    {
        usort($fileDecorators, function (FileDecoratorInterface $first, FileDecoratorInterface $second): int {
            return $second->getPriority() <=> $first->getPriority();
        });

        return $fileDecorators;
    }
}
