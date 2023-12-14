<?php
namespace Helhum\DotEnvConnector\Tests\Unit;

use Composer\Autoload\ClassLoader;
use Helhum\DotEnvConnector\Adapter\SymfonyDotEnv;
use Helhum\DotEnvConnector\Config;
use Helhum\DotEnvConnector\IncludeFile;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophet;

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
        if (file_exists(__DIR__ . '/Fixtures/foo')) {
            chmod(__DIR__ . '/Fixtures/foo', 777);
            rmdir(__DIR__ . '/Fixtures/foo');
        }
    }

    /**
     * @test
     */
    public function dumpDumpsFile()
    {
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        /** @var ClassLoader|\PHPUnit\Framework\MockObject\MockObject $loaderMock */
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
    public function includingFileExposesEnvVars()
    {
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        /** @var ClassLoader|\PHPUnit\Framework\MockObject\MockObject $loaderMock */
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
    public function includingFileDoesNothingIfEnvVarSet()
    {
        putenv('APP_ENV=1');
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        /** @var ClassLoader|\PHPUnit\Framework\MockObject\MockObject $loaderMock */
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
    public function includingFileDoesNothingIfEnvFileDoesNotExist()
    {
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.no-env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        /** @var ClassLoader|\PHPUnit\Framework\MockObject\MockObject $loaderMock */
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
    public function dumpReturnsFalseIfFileCannotBeWritten()
    {
        $config = new Config();
        $config->merge(['extra' => ['helhum/dotenv-connector' => [
            'env-file' => __DIR__ . '/Fixtures/env/.no-env',
            'adapter' => SymfonyDotEnv::class
        ]]]);
        /** @var ClassLoader|\PHPUnit\Framework\MockObject\MockObject $loaderMock */
        $loaderMock = $this->createMock(ClassLoader::class);
        $loaderMock->expects($this->once())->method('register');
        $loaderMock->expects($this->once())->method('unregister');

        mkdir(__DIR__ . '/Fixtures/foo', 000);
        $includeFilePath = __DIR__ . '/Fixtures/foo/include.php';
        $includeFile = new IncludeFile($config, $loaderMock, $includeFilePath);
        $this->assertFalse($includeFile->dump());
    }
}
