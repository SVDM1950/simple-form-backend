<?php

namespace App\Routing\Trait;

use Pimple\Container;

trait ContainerAware
{
    protected Container $container;

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
}
