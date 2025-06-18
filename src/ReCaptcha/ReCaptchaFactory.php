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
        $enabled = filter_var($this->config()->get("recaptcha.{$section}.enabled"), FILTER_VALIDATE_BOOLEAN);
        if (!$enabled) {
            return null;
        }

        return (new Recaptcha($this->config()->get("recaptcha.{$section}.siteSecret")))
            ->setExpectedHostname($this->config()->get("recaptcha.{$section}.siteUrl"))
            ->setExpectedAction($this->config()->get("recaptcha.{$section}.actionName"));
    }

    public function config(): Config
    {
        return $this->container[Config::class];
    }
}
