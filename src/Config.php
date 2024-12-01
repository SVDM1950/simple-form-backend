<?php

namespace App;

use DirectoryIterator;
use Illuminate\Config\Repository;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Config extends Repository
{
    /**
     * Separator between config entries
     */
    public const string CONFIG_SEPARATOR = '.';

    /**
     * Files to exclude from loading
     */
    private array $excludedFiles = [
        '.gitignore',
        '.gitkeep',
        '.git',
        '.svn',
        '.DS_Store',
        '.dockerignore'
    ];

    /**
     * Add a file to the exclude list
     */
    public function addExclude(string $file): void
    {
        $this->excludedFiles[] = $file;
    }

    /**
     * Add multiple files to the exclude list
     */
    public function addExcludes(string|array ...$files): void
    {
        if(count($files) === 1 && is_array($files[0])) {
            $files = $files[0];
        }

        $this->excludedFiles = array_merge($this->excludedFiles, $files);
    }

    /**
     * Remove a file from the exclude list
     */
    public function removeExclude(string $file): void
    {
        $key = array_search($file, $this->excludedFiles, true);
        if ($key !== false) {
            unset($this->excludedFiles[$key]);
        }
    }

    /**
     * Remove multiple files from the exclude list
     */
    public function removeExcludes(string|array ...$files): void
    {
        if(count($files) === 1 && is_array($files[0])) {
            $files = $files[0];
        }

        $this->excludedFiles = array_diff($this->excludedFiles, $files);
    }

    /**
     * Check if a file is excluded
     */
    public function hasExclude(string $file): bool
    {
        return in_array($file, $this->excludedFiles, true);
    }

    /**
     * Get all excluded files
     */
    public function getExcluded(): array
    {
        return $this->excludedFiles;
    }

    /**
     * Set all excluded files
     */
    public function setExcluded(array $excluded): void
    {
        $this->excludedFiles = $excluded;
    }

    /**
     * Clear all excluded files
     */
    public function clearExcluded(): void
    {
        $this->excludedFiles = [];
    }

    /**
     * Load config from path
     */
    public function loadConfigurationFile(string $configPath, string $prefix = null): void
    {
        $file = new SplFileInfo($configPath);
        if (!$file->isReadable()) {
            throw new RuntimeException('Could not load config: ' . $configPath);
        }
        $configPrefix = (isset($prefix)) ? $prefix . self::CONFIG_SEPARATOR : '';
        $configKey = $configPrefix . $file->getBasename(self::CONFIG_SEPARATOR . $file->getExtension());

        $this->set($configKey, $this->loadFile($file));
    }

    /**
     * Load all configs from directory recursive
     *
     * Results in configKeys like 'Dir.SubDir.FileName.configKey'
     */
    public function loadConfigurationDirectory(string $directory, string $prefix = null, bool $recursive = false): void
    {
        foreach (new DirectoryIterator($directory) as $file) {
            if ($file->isDot()) {
                continue;
            } elseif (in_array($file->getFilename(), $this->excludedFiles, true)) {
                continue;
            } elseif ($file->isFile() && $file->isReadable()) {
                $this->loadConfigurationFile($file->getPathname(), $prefix);
            } elseif ($file->isDir() && $recursive) {
                $dirPrefix = (isset($prefix))
                    ? $prefix . self::CONFIG_SEPARATOR . $file->getFilename()
                    : $file->getFilename();
                $this->loadConfigurationDirectory($file->getPathname(), $dirPrefix, $recursive);
            }
        }
    }

    /**
     * Load a file by given fileInfo
     *
     * @throws InvalidArgumentException
     * @throws JsonException
     * @throws ParseException
     */
    protected function loadFile(SplFileInfo $file): array
    {
        return match ($file->getExtension()) {
            'php'         => require $file->getPathname(),
            'yml', 'yaml' => Yaml::parseFile($file->getPathname()),
            'json'        => json_decode(file_get_contents($file->getPathname()), flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY),
            default       => throw new InvalidArgumentException('Unexpected fileType. Got ' . $file->getExtension())
        };
    }
}
