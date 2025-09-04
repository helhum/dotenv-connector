<?php
namespace Helhum\DotEnvConnector\Tests\Unit;

use Composer\Autoload\ClassLoader;
use Helhum\DotEnvConnector\Adapter\SymfonyDotEnv;
use Helhum\DotEnvConnector\Config;
use Helhum\DotEnvConnector\IncludeFile;
use PHPUnit\Framework\TestCase;

class IncludeFileTest extends TestCase
{
    protected function tearDown(): void
    {
        if (file_exists(__DIR__ . '/Fixtures/vendor/helhum/include.php')) {
            unlink(__DIR__ . '/Fixtures/vendor/helhum/include.php');
            rmdir(__DIR__ . '/Fixtures/vendor/helhum');
            rmdir(__DIR__ . '/Fixtures/vendor');
        }
        putenv('FOO');
        putenv('APP_ENV');
        putenv('DOTENV_CONNECTOR_OVERRIDE');
        unset($_ENV['FOO'], $_ENV['APP_ENV'], $_ENV['DOTENV_CONNECTOR_OVERRIDE'], $_ENV['SYMFONY_DOTENV_VARS']);
        unset($_SERVER['FOO'], $_SERVER['APP_ENV'], $_SERVER['DOTENV_CONNECTOR_OVERRIDE'], $_SERVER['SYMFONY_DOTENV_VARS']);
        if (file_exists(__DIR__ . '/Fixtures/foo')) {
            chmod(__DIR__ . '/Fixtures/foo', 777);
            rmdir(__DIR__ . '/Fixtures/foo');
        }
    }

    /**
     * @test
     */
    public function dumpDumpsFile(): void
    {
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        $loaderMock = $this->createMock(ClassLoader::class);
        $loaderMock->expects($this->once())->method('register');
        $loaderMock->expects($this->once())->method('unregister');

        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($config, $loaderMock, $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));
    }

    /**
     * @test
     */
    public function includingFileExposesEnvVars(): void
    {
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        $loaderMock = $this->createMock(ClassLoader::class);
        $loaderMock->expects($this->once())->method('register');
        $loaderMock->expects($this->once())->method('unregister');

        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($config, $loaderMock, $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));

        $this->assertSame('bar', getenv('FOO'));
    }

    /**
     * @test
     */
    public function includingFileDoesNothingIfEnvVarSet(): void
    {
        putenv('APP_ENV=1');
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        $loaderMock = $this->createMock(ClassLoader::class);
        $loaderMock->expects($this->once())->method('register');
        $loaderMock->expects($this->once())->method('unregister');

        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($config, $loaderMock, $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));

        $this->assertFalse(getenv('FOO'));
    }

    /**
     * @test
     */
    public function includingFileDoesNothingIfEnvFileDoesNotExist(): void
    {
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.no-env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        $loaderMock = $this->createMock(ClassLoader::class);
        $loaderMock->expects($this->once())->method('register');
        $loaderMock->expects($this->once())->method('unregister');

        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($config, $loaderMock, $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));

        $this->assertFalse(getenv('FOO'));
    }

    /**
     * @test
     */
    public function dumpReturnsFalseIfFileCannotBeWritten(): void
    {
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.no-env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        $loaderMock = $this->createMock(ClassLoader::class);
        $loaderMock->expects($this->once())->method('register');
        $loaderMock->expects($this->once())->method('unregister');

        mkdir(__DIR__ . '/Fixtures/foo', 000);
        $includeFilePath = __DIR__ . '/Fixtures/foo/include.php';
        $includeFile = new IncludeFile($config, $loaderMock, $includeFilePath);
        $this->assertFalse($includeFile->dump());
    }

    /**
     * @test
     */
    public function includingFileDoesNotOverrideExistingEnvVars(): void
    {
        putenv('FOO=baz');
        $_ENV['FOO'] = 'baz';
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        $loaderMock = $this->createMock(ClassLoader::class);
        $loaderMock->expects($this->once())->method('register');
        $loaderMock->expects($this->once())->method('unregister');

        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($config, $loaderMock, $includeFilePath);
        $includeFile->dump();
        $this->assertFileExists($includeFilePath);

        $this->assertSame('baz', getenv('FOO'));
    }

    /**
     * @test
     */
    public function includingFileDoesOverrideExistingEnvVars(): void
    {
        putenv('FOO=baz');
        $_ENV['FOO'] = 'baz';
        putenv('DOTENV_CONNECTOR_OVERRIDE=1');
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        $loaderMock = $this->createMock(ClassLoader::class);
        $loaderMock->expects($this->once())->method('register');
        $loaderMock->expects($this->once())->method('unregister');

        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($config, $loaderMock, $includeFilePath);
        $includeFile->dump();
        $this->assertFileExists($includeFilePath);

        $this->assertSame('bar', getenv('FOO'));
    }
}
