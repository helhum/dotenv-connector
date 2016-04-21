<?php
/**
 * This file is part of the typo3 console project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 */
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

/**
 * Class DotEnvReaderTest
 */
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
        $envDir = __DIR__ . '/Fixtures/env';
        $reader = new DotEnvReader(new Dotenv($envDir), $cacheMock);
        $reader->read();
        $this->assertSame('bar', getenv('FOO'));
    }

    /**
     * @test
     */
    public function readerDisablesOverrideEnvVarsByDefault()
    {
        $cacheMock = $this->getMockBuilder('Helhum\\DotEnvConnector\\Cache')->disableOriginalConstructor()->getMock();
        $envDir = __DIR__ . '/Fixtures/env';
        $reader = new DotEnvReader(new Dotenv($envDir), $cacheMock);
        putenv('FOO=baz');
        $reader->read();
        $this->assertSame('baz', getenv('FOO'));
    }

    /**
     * @test
     */
    public function readerOverridesEnvVars()
    {
        $cacheMock = $this->getMockBuilder('Helhum\\DotEnvConnector\\Cache')->disableOriginalConstructor()->getMock();
        $envDir = __DIR__ . '/Fixtures/env';
        $reader = new DotEnvReader(new Dotenv($envDir), $cacheMock, true);
        putenv('FOO=baz');
        $reader->read();
        $this->assertSame('bar', getenv('FOO'));
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
        $envDir = __DIR__ . '/Fixtures/env';
        $reader = new DotEnvReader(new Dotenv($envDir), $cacheMock);
        $reader->read();
        $this->assertSame('bar', getenv('FOO'));
    }
}
