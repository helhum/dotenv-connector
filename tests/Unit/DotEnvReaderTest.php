<?php
namespace Helhum\DotEnvConnector\tests\Unit;

/*
 * This file is part of the dotenv connector package.
 *
 * (c) Helmut Hummel <info@helhum.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Dotenv\Dotenv;
use Helhum\DotEnvConnector\DotEnvReader;

class DotEnvReaderTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        // Cleanup single env var used
        putenv('FOO');
    }

    /**
     * @test
     */
    public function readerExposesVarsInFile()
    {
        $cacheMock = $this->getMockBuilder('Helhum\\DotEnvConnector\\Cache')->disableOriginalConstructor()->getMock();
        $envFile = __DIR__ . '/Fixtures/env/.env';
        $reader = new DotEnvReader(new Dotenv(dirname($envFile)), $cacheMock);
        $reader->read();
        $this->assertSame('bar', getenv('FOO'));
    }

    /**
     * @test
     */
    public function readerDisablesOverrideEnvVarsByDefault()
    {
        $cacheMock = $this->getMockBuilder('Helhum\\DotEnvConnector\\Cache')->disableOriginalConstructor()->getMock();
        $envFile = __DIR__ . '/Fixtures/env/.env';
        $reader = new DotEnvReader(new Dotenv(dirname($envFile)), $cacheMock);
        putenv('FOO=baz');
        $reader->read();
        $this->assertSame('baz', getenv('FOO'));
    }

    /**
     * @test
     */
    public function readerDoesNotOverrideExistingEnvVars()
    {
        $cacheMock = $this->getMockBuilder('Helhum\\DotEnvConnector\\Cache')->disableOriginalConstructor()->getMock();
        $envFile = __DIR__ . '/Fixtures/env/.env';
        $reader = new DotEnvReader(new Dotenv(dirname($envFile)), $cacheMock);
        putenv('FOO=baz');
        $reader->read();
        $this->assertSame('baz', getenv('FOO'));
    }

    /**
     * @test
     */
    public function cacheCodeIsWrittenToCacheIfConfigured()
    {
        $cacheMock = $this->getMockBuilder('Helhum\\DotEnvConnector\\Cache')->disableOriginalConstructor()->getMock();
        $cacheMock->expects($this->any())->method('isEnabled')->willReturn(true);
        $cacheMock->expects($this->any())->method('storeCache')
            ->with('<?php
putenv(\'FOO=bar\');
$_ENV[\'FOO\'] = \'bar\';
$_SERVER[\'FOO\'] = \'bar\';
');
        $envFile = __DIR__ . '/Fixtures/env/.env';
        $reader = new DotEnvReader(new Dotenv(dirname($envFile)), $cacheMock);
        $reader->read();
        $this->assertSame('bar', getenv('FOO'));
    }
}
