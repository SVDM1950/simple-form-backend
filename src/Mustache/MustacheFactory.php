<?php

namespace App\Mustache;

use App\Routing\Interface\ContainerAware;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use DateTime;
use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;
use Pimple\Container;

class MustacheFactory implements ContainerAware
{
    use ContainerAwareTrait;

    public function __construct(protected Container $container)
    {
    }

    public function __invoke(): Mustache_Engine
    {
        return new Mustache_Engine([
            'entity_flags' => ENT_QUOTES,
            # 'cache' => dirname(__FILE__).'/../var/cache/mustache',
            'loader' => new Mustache_Loader_FilesystemLoader('templates'),
            'partials_loader' => new Mustache_Loader_FilesystemLoader('templates/partials'),
            # 'logger' => $this->logger(),
            'charset' => 'UTF-8',
            'strict_callables' => true,
            'pragmas' => [Mustache_Engine::PRAGMA_FILTERS],
            'helpers' => [
                'date' => fn(string $date) => (new DateTime($date))->format('d.m.Y'),
                'time' => fn(string $date) => (new DateTime($date))->format('H:i'),
                'datetime' => fn(string $date) => (new DateTime($date))->format('d.m.Y H:i'),
                'lastPayDate' => fn(string $date) => (new DateTime($date . ' - 4 days'))->format('d.m.Y'),
                'currency' => fn(string $value) => number_format((float) $value, 2, ',', '.'),
            ]
        ]);
    }
}
