<?php
namespace Helhum\DotEnvConnector\Tests\Unit;

use Composer\Autoload\ClassLoader;
use Helhum\DotEnvConnector\Adapter\SymfonyDotEnv;
use Helhum\DotEnvConnector\Config;
use Helhum\DotEnvConnector\IncludeFile;

class IncludeFileTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
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
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.env');
        $configProphecy->get('adapter')->willReturn(SymfonyDotEnv::class);
        $loaderProphecy = $this->prophesize(ClassLoader::class);
        $loaderProphecy->register()->shouldBeCalled();
        $loaderProphecy->unregister()->shouldBeCalled();

        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $loaderProphecy->reveal(), $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));
    }

    /**
     * @test
     */
    public function includingFileExposesEnvVars()
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.env');
        $configProphecy->get('adapter')->willReturn(SymfonyDotEnv::class);
        $loaderProphecy = $this->prophesize(ClassLoader::class);
        $loaderProphecy->register()->shouldBeCalled();
        $loaderProphecy->unregister()->shouldBeCalled();

        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $loaderProphecy->reveal(), $includeFilePath);
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
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.env');
        $configProphecy->get('adapter')->willReturn(SymfonyDotEnv::class);
        $loaderProphecy = $this->prophesize(ClassLoader::class);
        $loaderProphecy->register()->shouldBeCalled();
        $loaderProphecy->unregister()->shouldBeCalled();

        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $loaderProphecy->reveal(), $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));

        $this->assertFalse(getenv('FOO'));
    }

    /**
     * @test
     */
    public function includingFileDoesNothingIfEnvFileDoesNotExist()
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.no-env');
        $configProphecy->get('adapter')->willReturn(SymfonyDotEnv::class);
        $loaderProphecy = $this->prophesize(ClassLoader::class);
        $loaderProphecy->register()->shouldBeCalled();
        $loaderProphecy->unregister()->shouldBeCalled();

        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $loaderProphecy->reveal(), $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));

        $this->assertFalse(getenv('FOO'));
    }

    /**
     * @test
     */
    public function dumpReturnsFalseIfFileCannotBeWritten()
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.no-env');
        $configProphecy->get('adapter')->willReturn(SymfonyDotEnv::class);
        $loaderProphecy = $this->prophesize(ClassLoader::class);
        $loaderProphecy->register()->shouldBeCalled();
        $loaderProphecy->unregister()->shouldBeCalled();

        mkdir(__DIR__ . '/Fixtures/foo', 000);
        $includeFilePath = __DIR__ . '/Fixtures/foo/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $loaderProphecy->reveal(), $includeFilePath);
        $this->assertFalse($includeFile->dump());
    }
}
