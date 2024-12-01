<?php

namespace App\Routing\Interface;

use Pimple\Container;

interface ContainerAware
{
    public function setContainer(Container $container): void;
}
