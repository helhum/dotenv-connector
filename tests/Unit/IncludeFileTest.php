<?php
namespace Helhum\DotEnvConnector\Tests\Unit;

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
        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $includeFilePath);
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
        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));
        require $includeFilePath;
        $this->assertSame('bar', getenv('FOO'));
    }

    /**
     * @test
     */
    public function includingFileDoesNothingIfEnvVarSet()
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.env');
        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));
        putenv('APP_ENV=1');
        require $includeFilePath;
        $this->assertFalse(getenv('FOO'));
    }

    /**
     * @test
     */
    public function includingFileDoesNothingIfEnvFileDoesNotExist()
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.no-env');
        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));
        require $includeFilePath;
        $this->assertFalse(getenv('FOO'));
    }

    /**
     * @test
     */
    public function dumpReturnsFalseIfFileCannotBeWritten()
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.no-env');
        mkdir(__DIR__ . '/Fixtures/foo', 000);
        $includeFilePath = __DIR__ . '/Fixtures/foo/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $includeFilePath);
        $this->assertFalse($includeFile->dump());
    }
}
