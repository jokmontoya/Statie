<?php

declare(strict_types=1);

namespace Symplify\Statie\Generator;

use Symplify\Statie\Configuration\StatieConfiguration;
use Symplify\Statie\FileSystem\FileFinder;
use Symplify\Statie\Generator\Configuration\GeneratorConfiguration;
use Symplify\Statie\Generator\Configuration\GeneratorElement;
use Symplify\Statie\Generator\Exception\Configuration\GeneratorException;
use Symplify\Statie\Generator\Renderable\File\AbstractGeneratorFile;
use Symplify\Statie\Generator\Renderable\File\GeneratorFileFactory;
use Symplify\Statie\Renderable\RenderableFilesProcessor;

final class Generator
{
    /**
     * @var AbstractGeneratorFile[][]
     */
    private $generatorFilesByType = [];

    /**
     * @var GeneratorConfiguration
     */
    private $generatorConfiguration;

    /**
     * @var FileFinder
     */
    private $fileFinder;

    /**
     * @var StatieConfiguration
     */
    private $statieConfiguration;

    /**
     * @var RenderableFilesProcessor
     */
    private $renderableFilesProcessor;

    /**
     * @var GeneratorFileFactory
     */
    private $generatorFileFactory;

    public function __construct(
        GeneratorConfiguration $generatorConfiguration,
        FileFinder $fileFinder,
        StatieConfiguration $statieConfiguration,
        RenderableFilesProcessor $renderableFilesProcessor,
        GeneratorFileFactory $generatorFileFactory
    ) {
        $this->generatorConfiguration = $generatorConfiguration;
        $this->fileFinder = $fileFinder;
        $this->statieConfiguration = $statieConfiguration;
        $this->renderableFilesProcessor = $renderableFilesProcessor;
        $this->generatorFileFactory = $generatorFileFactory;
    }

    /**
     * @return AbstractGeneratorFile[][]
     */
    public function run(): array
    {
        if ($this->generatorFilesByType !== []) {
            return $this->generatorFilesByType;
        }

        foreach ($this->generatorConfiguration->getGeneratorElements() as $generatorElement) {
            if (! is_dir($generatorElement->getPath())) {
                $this->reportMissingPath($generatorElement);

                continue;
            }

            $objects = $this->createObjectsFromFoundElements($generatorElement);

            // save them to property (for "related_items" option)
            $this->statieConfiguration->addOption($generatorElement->getVariableGlobal(), $objects);

            $generatorElement->setObjects($objects);
        }

        $generatorFilesByType = [];
        foreach ($this->generatorConfiguration->getGeneratorElements() as $generatorElement) {
            $key = $generatorElement->getVariableGlobal();
            if (isset($generatorFilesByType[$key])) {
                throw new GeneratorException(sprintf(
                    'Generator element for "%s" global variable already exists.',
                    $key
                ));
            }

            // run them through decorator and render content to string
            $generatorFilesByType[$key] = $this->renderableFilesProcessor->processGeneratorElementObjects(
                $generatorElement->getObjects(),
                $generatorElement
            );
        }

        $this->generatorFilesByType = $generatorFilesByType;

        return $this->generatorFilesByType;
    }

    private function reportMissingPath(GeneratorElement $generatorElement): void
    {
        if ($generatorElement->getVariableGlobal() !== 'posts') {
            throw new GeneratorException(sprintf(
                'Path "%s" for generated element "%s" was not found.',
                $generatorElement->getPath(),
                $generatorElement->getVariableGlobal()
            ));
        }
    }

    /**
     * @return AbstractGeneratorFile[]
     */
    private function createObjectsFromFoundElements(GeneratorElement $generatorElement): array
    {
        $fileInfos = $this->fileFinder->findInDirectoryForGenerator($generatorElement->getPath());
        if (count($fileInfos) === 0) {
            return [];
        }

        // process to objects
        return $this->generatorFileFactory->createFromFileInfosAndClass($fileInfos, $generatorElement->getObject());
    }
}
