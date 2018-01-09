<?php
namespace Helhum\DotEnvConnector\Tests\Unit;

use Helhum\DotEnvConnector\Config;
use Helhum\DotEnvConnector\IncludeFile;

class IncludeFileTest extends \PHPUnit_Framework_TestCase
{

    public function includeFileDataProvider() {
        return [
            'useRelativePath' => [
                'useAbsolutePath' => false
            ],
            'useAbsolutePath' => [
                'useAbsolutePath' => true
            ]
        ];
    }

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
     * @dataProvider includeFileDataProvider
     */
    public function dumpDumpsFile($useAbsoultePath)
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.env');
        $configProphecy->get('use-absoulte-path')->willReturn($useAbsoultePath);
        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));
    }

    /**
     * @test
     * @dataProvider includeFileDataProvider
     */
    public function includingFileExposesEnvVars($useAbsoultePath)
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.env');
        $configProphecy->get('use-absoulte-path')->willReturn($useAbsoultePath);
        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));
        require $includeFilePath;
        $this->assertSame('bar', getenv('FOO'));
    }

    /**
     * @test
     * @dataProvider includeFileDataProvider
     */
    public function includingFileDoesNothingIfEnvVarSet($useAbsoultePath)
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.env');
        $configProphecy->get('use-absoulte-path')->willReturn($useAbsoultePath);
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
     * @dataProvider includeFileDataProvider
     */
    public function includingFileDoesNothingIfEnvFileDoesNotExist($useAbsoultePath)
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.no-env');
        $configProphecy->get('use-absoulte-path')->willReturn($useAbsoultePath);
        $includeFilePath = __DIR__ . '/Fixtures/vendor/helhum/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $includeFilePath);
        $includeFile->dump();
        $this->assertTrue(file_exists($includeFilePath));
        require $includeFilePath;
        $this->assertFalse(getenv('FOO'));
    }

    /**
     * @test
     * @dataProvider includeFileDataProvider
     */
    public function dumpReturnsFalseIfFileCannotBeWritten($useAbsoultePath)
    {
        $configProphecy = $this->prophesize(Config::class);
        $configProphecy->get('env-file')->willReturn(__DIR__ . '/Fixtures/env/.no-env');
        $configProphecy->get('use-absoulte-path')->willReturn($useAbsoultePath);
        mkdir(__DIR__ . '/Fixtures/foo', 000);
        $includeFilePath = __DIR__ . '/Fixtures/foo/include.php';
        $includeFile = new IncludeFile($configProphecy->reveal(), $includeFilePath);
        $this->assertFalse($includeFile->dump());
    }
}
