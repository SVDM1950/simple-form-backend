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
            } elseif ($file->isFile() and $file->isReadable()) {
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
