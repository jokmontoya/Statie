<?php

declare(strict_types=1);

namespace Symplify\Statie\FileSystem;

use Symfony\Component\Finder\Finder;
use Symplify\SmartFileSystem\Finder\FinderSanitizer;
use Symplify\SmartFileSystem\SmartFileInfo;
use Symplify\Statie\Exception\Configuration\DuplicatedPostException;
use Symplify\Statie\Generator\Configuration\GeneratorElement;

final class CreatePostFileSystem
{
    /**
     * @var FinderSanitizer
     */
    private $finderSanitizer;

    public function __construct(FinderSanitizer $finderSanitizer)
    {
        $this->finderSanitizer = $finderSanitizer;
    }

    public function isNestedByYear(GeneratorElement $generatorElement): bool
    {
        // is nested directory approach?
        $finder = Finder::create()
            ->in($generatorElement->getPath())
            ->directories()
            ->name('#\d+#');

        return (bool) iterator_to_array($finder->getIterator());
    }

    /**
     * @return SmartFileInfo[]
     */
    public function findMarkdownFilesInGeneratorElement(GeneratorElement $generatorElement): array
    {
        $finder = Finder::create()->files()
            ->in($generatorElement->getPath())
            ->name('*.md');

        return $this->finderSanitizer->sanitize($finder);
    }

    public function ensureFilePathIsNew(string $postFilePath): void
    {
        if (! file_exists($postFilePath)) {
            return;
        }

        throw new DuplicatedPostException(sprintf('Post "%s" already exists, change the name or date.', $postFilePath));
    }
}
