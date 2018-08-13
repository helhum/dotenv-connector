<?php
namespace Helhum\DotEnvConnector\Tests\Unit;

use Composer\Autoload\ClassLoader;
use Helhum\DotEnvConnector\Config;
use Helhum\DotEnvConnector\IncludeFile;

class IncludeFileTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        @unlink(__DIR__ . '/Fixtures/vendor/helhum/include.php');
        putenv('FOO');
        putenv('APP_ENV');
        @chmod(__DIR__ . '/Fixtures/foo', 777);
        @rmdir(__DIR__ . '/Fixtures/foo');
    }

    /**
     * @test
     */
    public function dumpDumpsFile()
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.env');
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
        $loaderProphecy = $this->prophesize(ClassLoader::class);
        $loaderProphecy->register()->shouldNotBeCalled();
        $loaderProphecy->unregister()->shouldNotBeCalled();

        mkdir(__DIR__ . '/Fixtures/foo', 000);
        $includeFilePath = __DIR__ . '/Fixtures/foo/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $loaderProphecy->reveal(), $includeFilePath);
        $this->assertFalse($includeFile->dump());
    }
}
