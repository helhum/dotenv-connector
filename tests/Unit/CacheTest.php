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

namespace Helhum\DotEnvConnector\Tests\Unit;

/*
 * This file is part of the dotenv connector package.
 *
 * (c) Helmut Hummel <info@helhum.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Helhum\DotEnvConnector\Cache;

/**
 * Class CacheTest
 */
class CacheTest extends \PHPUnit_Framework_TestCase
{
    protected function tearDown()
    {
        $cacheDir = __DIR__ . '/Fixtures/cache';
        foreach (glob($cacheDir . '/*.php') as $file) {
            unlink($file);
        };

    }

    /**
     * @test
     */
    public function isEnabledReturnsFalseWhenDirIsNotThere()
    {
        $cache = new Cache('/foo/not/here', __DIR__ . '/Fixtures/env');
        $this->assertFalse($cache->isEnabled());
    }

    /**
     * @test
     */
    public function isEnabledReturnsFalseWhenDirIsEmpty()
    {
        $cache = new Cache('', __DIR__ . '/Fixtures/env');
        $this->assertFalse($cache->isEnabled());
    }

    /**
     * @test
     */
    public function isEnabledReturnsFalseWhenDirIsNotWritable()
    {
        $cacheDir = __DIR__ . '/Fixtures/cache';
        $oldPerms = fileperms($cacheDir);
        chmod($cacheDir, 0000);
        $cache = new Cache($cacheDir, __DIR__ . '/Fixtures/env');
        $this->assertFalse($cache->isEnabled());
        chmod($cacheDir, $oldPerms);
    }

    /**
     * @test
     */
    public function isEnabledReturnsTrueWhenDirIsWritable()
    {
        $cacheDir = __DIR__ . '/Fixtures/cache';
        $cache = new Cache($cacheDir, __DIR__ . '/Fixtures/env');
        $this->assertTrue($cache->isEnabled());
    }

    /**
     * @test
     */
    public function storeWritesAFileToCacheDir()
    {
        $cacheDir = __DIR__ . '/Fixtures/cache';
        $cache = new Cache($cacheDir, __DIR__ . '/Fixtures/env');
        $cache->storeCache('<?php' . PHP_EOL . '$GLOBALS[\'BLA\']=\'blupp\';');
        $this->assertSame(1, count(glob($cacheDir . '/*.php')));
    }

    /**
     * @test
     */
    public function storeWritesAFileToCacheReturnsTrueForHas()
    {
        $cacheDir = __DIR__ . '/Fixtures/cache';
        $cache = new Cache($cacheDir, __DIR__ . '/Fixtures/env');
        $cache->storeCache('<?php' . PHP_EOL . '$GLOBALS[\'BLA\']=\'blupp\';');
        $this->assertTrue($cache->hasCache());
    }

    /**
     * @test
     */
    public function loadRequiresCacheFile()
    {
        $cacheDir = __DIR__ . '/Fixtures/cache';
        $cache = new Cache($cacheDir, __DIR__ . '/Fixtures/env');
        $cache->storeCache('<?php' . PHP_EOL . '$GLOBALS[\'BLA\'] = \'blupp\';');
        $cache->loadCache();
        $this->assertSame('blupp', $GLOBALS['BLA']);
    }

    /**
     * @test
     */
    public function touchChangesCacheFile()
    {
        $cacheDir = __DIR__ . '/Fixtures/cache';
        $envFilePath = __DIR__ . '/Fixtures/env';
        $cache = new Cache($cacheDir, $envFilePath);
        $cache->storeCache('<?php' . PHP_EOL . '$GLOBALS[\'BLA\'] = \'blupp\';');
        $origContent = file_get_contents($envFilePath . '/.env');
        file_put_contents($envFilePath . '/.env', $origContent . PHP_EOL);
        clearstatcache();
        $cache = new Cache($cacheDir, $envFilePath);
        $this->assertFalse($cache->hasCache());
        file_put_contents($envFilePath . '/.env', $origContent);
    }
}
