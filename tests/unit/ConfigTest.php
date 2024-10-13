<?php

namespace Tests\Unit;

use App\Config;
use Codeception\Attribute\Before;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use TypeError;

#[CoversClass(Config::class)]
#[CoversMethod(Config::class,'loadConfigurationFile')]
#[CoversMethod(Config::class, 'loadConfigurationDirectory')]
class ConfigTest extends Unit
{
    protected Config $config;

    #[Before]
    protected function _before(): void
    {
        $this->config = new Config();
    }

    #[Test]
    #[DataProvider('configFilesProvider')]
    public function loadConfigurationFile($filename)
    {
        $this->config->loadConfigurationFile(codecept_data_dir("configs/loadConfigurationFile/{$filename}"));

        $this->assertTrue($this->config->has('config'));
        $this->assertEquals(42, $this->config->get('config.testVal'));
        $this->assertTrue($this->config->has('config.properties'));
        $this->assertEquals(1, $this->config->get('config.properties.0'));
        $this->assertEquals(28, $this->config->get('config.properties.testVal'));
        $this->assertTrue($this->config->get('config.properties.bool'));
    }

    #[Test]
    #[DataProvider('configFilesProvider')]
    public function loadConfigurationFileWithPrefix($filename)
    {
        $this->config->loadConfigurationFile(codecept_data_dir("configs/loadConfigurationFile/{$filename}"), prefix: 'prefix');

        $this->assertTrue($this->config->has('prefix'));
        $this->assertTrue($this->config->has('prefix.config'));
        $this->assertEquals(42, $this->config->get('prefix.config.testVal'));
        $this->assertTrue($this->config->has('prefix.config.properties'));
        $this->assertEquals(1, $this->config->get('prefix.config.properties.0'));
        $this->assertEquals(28, $this->config->get('prefix.config.properties.testVal'));
        $this->assertTrue($this->config->get('prefix.config.properties.bool'));
    }

    #[Test]
    #[DataProvider('InvalidConfigFilesProvider')]
    public function loadInvalidConfigurationFile($filename, $exceptionClass)
    {
        $this->expectException($exceptionClass);

        $this->config->loadConfigurationFile(codecept_data_dir("configs/loadConfigurationFile/{$filename}"));
    }

    #[Test]
    public function loadConfigurationDirectory()
    {
        $this->config->loadConfigurationDirectory(codecept_data_dir('configs/loadConfigurationDirectory'));

        $this->assertTrue($this->config->has('jsonConfig'));
        $this->assertTrue($this->config->has('phpConfig'));
        $this->assertTrue($this->config->has('yamlConfig'));
    }

    #[Test]
    public function loadConfigurationDirectoryWithPrefix()
    {
        $this->config->loadConfigurationDirectory(codecept_data_dir('configs/loadConfigurationDirectory'), prefix: 'prefix');

        $this->assertTrue($this->config->has('prefix'));
        $this->assertTrue($this->config->has('prefix.jsonConfig'));
        $this->assertTrue($this->config->has('prefix.phpConfig'));
        $this->assertTrue($this->config->has('prefix.yamlConfig'));
    }

    #[Test]
    public function loadConfigurationDirectoryRecursive()
    {
        $this->config->loadConfigurationDirectory(codecept_data_dir('configs/loadConfigurationDirectory'), recursive: true);

        $this->assertTrue($this->config->has('jsonConfig'));
        $this->assertTrue($this->config->has('phpConfig'));
        $this->assertTrue($this->config->has('yamlConfig'));

        $this->assertEquals(42, $this->config->get('jsonConfig.testVal'));

        $this->assertTrue($this->config->has('subConfig'));
        $this->assertTrue($this->config->has('subConfig.backend'));
        $this->assertTrue($this->config->has('subConfig.phpConfig'));

        $this->assertTrue($this->config->get('subConfig.backend.testMode'));
        $this->assertEquals('bar', $this->config->get('subConfig.phpConfig.test'));

        $this->assertTrue($this->config->has('subConfig.subSubConfig'));
        $this->assertTrue($this->config->has('subConfig.subSubConfig.work'));

        $this->assertEquals(1, $this->config->get('subConfig.subSubConfig.work.hours-per-day'));
    }

    #[Test]
    public function loadConfigurationDirectoryRecursiveWithPrefix()
    {
        $this->config->loadConfigurationDirectory(codecept_data_dir('configs/loadConfigurationDirectory'), prefix: 'prefix', recursive: true);

        $this->assertTrue($this->config->has('prefix'));
        $this->assertTrue($this->config->has('prefix.jsonConfig'));
        $this->assertTrue($this->config->has('prefix.phpConfig'));
        $this->assertTrue($this->config->has('prefix.yamlConfig'));

        $this->assertEquals(42, $this->config->get('prefix.jsonConfig.testVal'));

        $this->assertTrue($this->config->has('prefix.subConfig'));
        $this->assertTrue($this->config->has('prefix.subConfig.backend'));
        $this->assertTrue($this->config->has('prefix.subConfig.phpConfig'));

        $this->assertTrue($this->config->get('prefix.subConfig.backend.testMode'));
        $this->assertEquals('bar', $this->config->get('prefix.subConfig.phpConfig.test'));

        $this->assertTrue($this->config->has('prefix.subConfig.subSubConfig'));
        $this->assertTrue($this->config->has('prefix.subConfig.subSubConfig.work'));

        $this->assertEquals(1, $this->config->get('prefix.subConfig.subSubConfig.work.hours-per-day'));
    }

    public static function configFilesProvider(): array
    {
        return [
            ['filename' => 'config.php'],
            ['filename' => 'config.yml'],
            ['filename' => 'config.yaml'],
            ['filename' => 'config.json'],
        ];
    }

    public static function invalidConfigFilesProvider(): array
    {
        return [
            ['filename' => 'notExists.php', 'exceptionClass' => RuntimeException::class],
            ['filename' => 'notSupported.xml', 'exceptionClass' => InvalidArgumentException::class],
            ['filename' => 'invalidConfig.php', 'exceptionClass' => TypeError::class],
            ['filename' => 'invalidConfig.yml', 'exceptionClass' => ParseException::class],
            ['filename' => 'invalidConfig.yaml', 'exceptionClass' => ParseException::class],
            ['filename' => 'invalidConfig.json', 'exceptionClass' => JsonException::class],
        ];
    }
}
