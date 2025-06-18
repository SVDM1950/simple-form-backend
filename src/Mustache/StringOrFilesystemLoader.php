<?php

declare(strict_types=1);

namespace App\Mustache;

use Mustache_Loader_FilesystemLoader;

/**
 * Filesystem loader that falls back to treating the name as template string
 */
class StringOrFilesystemLoader extends Mustache_Loader_FilesystemLoader
{
    private $templates = [];

    /**
     * Override loadFile to return the name as template if file doesn't exist
     */
    public function load($name): string
    {
        // Prüfen ob die Datei existiert
        if (!file_exists($this->getFileName($name))) {
            return $name;
        }
        
        // Datei existiert -> normale Datei laden
        return parent::load($name);
    }
}