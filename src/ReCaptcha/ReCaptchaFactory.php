<?php

namespace App\ReCaptcha;

use App\Config;
use App\Routing\Interface\ContainerAware;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use Pimple\Container;
use ReCaptcha\ReCaptcha;

class ReCaptchaFactory implements ContainerAware
{
    use ContainerAwareTrait;

    public function __construct(protected Container $container)
    {
    }

    public function __invoke(string $section): ?ReCaptcha
    {
        $config = $this->container[Config::class];

        $enabled = filter_var($config->get("recaptcha.{$section}.enabled"), FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) {
            return null;
        }

        return (new Recaptcha($config->get("recaptcha.{$section}.siteSecret")))
            ->setExpectedHostname($config->get("recaptcha.{$section}.siteUrl"))
            ->setExpectedAction($config->get("recaptcha.{$section}.actionName"));
    }
}
