<?php

namespace App\PHPMailer;

use App\Config;
use App\Routing\Interface\ContainerAware;
use App\Routing\Trait\ContainerAware as ContainerAwareTrait;
use PHPMailer\PHPMailer\PHPMailer;
use Pimple\Container;

class PHPMailerFactory implements ContainerAware
{
    use ContainerAwareTrait;

    public function __construct(protected Container $container)
    {
    }

    public function __invoke(string $section): PHPMailer
    {
        $config = $this->container[Config::class];

        $phpMailer = new PHPMailer(true);
        $phpMailer->isSMTP();
        $phpMailer->SMTPAuth = true;
        $phpMailer->Host = $config->get("mail.{$section}.host");
        $phpMailer->Username = $config->get("mail.{$section}.username");
        $phpMailer->Password = $config->get("mail.{$section}.password");
        $phpMailer->SMTPSecure = $config->get("mail.{$section}.encryption");
        $phpMailer->Port = $config->get("mail.{$section}.port");
        $phpMailer->CharSet = PHPMailer::CHARSET_UTF8;

        return $phpMailer;
    }
}
