<?php

declare(strict_types=1);

namespace Symplify\Statie\FileSystem;

use Symfony\Component\Finder\Finder;
use Symplify\SmartFileSystem\Finder\FinderSanitizer;
use Symplify\SmartFileSystem\SmartFileInfo;

final class FileFinder
{
    /**
     * @var string[]
     */
    private $staticFileExtensions = [
        'CNAME',
        '*.png',
        '*.jpg',
        '*.svg',
        '*.css',
        '*.ico',
        '*.js',
        '*.',
        '*.jpeg',
        '*.gif',
        '*.zip',
        '*.tgz',
        '*.gz',
        '*.rar',
        '*.bz2',
        '*.pdf',
        '*.txt',
        '*.tar',
        '*.mp3',
        '*.doc',
        '*.xls',
        '*.pdf',
        '*.ppt',
        '*.txt',
        '*.tar',
        '*.bmp',
        '*.rtf',
        '*.woff2',
        '*.woff',
        '*.otf',
        '*.ttf',
        '*.eot',
    ];

    /**
     * @var FinderSanitizer
     */
    private $finderSanitizer;

    public function __construct(FinderSanitizer $finderSanitizer)
    {
        $this->finderSanitizer = $finderSanitizer;
    }

    /**
     * @return SmartFileInfo[]
     */
    public function findLayoutsAndSnippets(string $directory): array
    {
        $finder = Finder::create()->files()
            ->in($directory)
            # @todo turn to parameters
            ->path('#(_layouts|_snippets)#');

        return $this->finderSanitizer->sanitize($finder);
    }

    /**
     * @return SmartFileInfo[]
     */
    public function findInDirectoryForGenerator(string $directoryPath): array
    {
        $directoryInfo = new SmartFileInfo($directoryPath);
        $path = $this->normalizePath($directoryInfo->getFilename() . DIRECTORY_SEPARATOR);
        $pathPattern = '#' . preg_quote($path, '#') . '#';

        $finder = Finder::create()->files()
            ->in($directoryInfo->getPath())
            ->path($pathPattern);

        return $this->finderSanitizer->sanitize($finder);
    }

    /**
     * @return SmartFileInfo[]
     */
    public function findStaticFiles(string $directory): array
    {
        $finder = Finder::create()->files()
            ->in($directory);

        foreach ($this->staticFileExtensions as $name) {
            $finder->name($name);
        }

        return $this->finderSanitizer->sanitize($finder);
    }

    /**
     * @return SmartFileInfo[]
     */
    public function findRestOfRenderableFiles(string $directory): array
    {
        $finder = Finder::create()->files()
            ->name('*.html')
            ->name('*.twig')
            ->name('*.rss')
            ->name('*.md')
            ->name('*.xml')
            ->notPath('#(_layouts|_snippets)#')
            ->in($directory);

        return $this->finderSanitizer->sanitize($finder);
    }

    private function normalizePath(string $path): string
    {
        return strtr($path, DIRECTORY_SEPARATOR, '/');
    }
}
