<?php
namespace Helhum\DotEnvConnector;

    /*
 * This file is part of the dotenv connector package.
 *
 * (c) Helmut Hummel <info@helhum.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Class Cache
 */
class Cache
{
    const CACHE_FILE_PATTERN = '/dotenv-cache-%s.php';

    /**
     * Absolute path to a (writable) cache directory (or empty for disabled cache)
     *
     * @var string
     */
    protected $cacheDirectory;
    protected $dotEnvFile;
    protected $cacheFileName;

    public function __construct($cacheDirectory, $dotEnvDirectory)
    {
        $this->cacheDirectory = $cacheDirectory;
        $this->dotEnvFile = $dotEnvDirectory . '/.env';
    }

    public function isEnabled()
    {
        return !empty($this->cacheDirectory)
            && is_dir($this->cacheDirectory)
            && is_writable($this->cacheDirectory);
    }

    public function hasCache()
    {
        return @file_exists($this->getCacheFileName());
    }

    public function loadCache()
    {
        require $this->getCacheFileName();
    }

    public function storeCache($content)
    {
        return file_put_contents($this->getCacheFileName(), $content);
    }

    public function cleanCache()
    {
        foreach (glob($this->getCacheFileGlob()) as $file) {
            @unlink($file);
        }
    }

    protected function getCacheFileName()
    {
        if (empty($this->cacheFileName)) {
            $this->cacheFileName = $this->cacheDirectory . sprintf(self::CACHE_FILE_PATTERN, md5('cache_id_' . filemtime($this->dotEnvFile)));
        }
        return $this->cacheFileName;
    }

    protected function getCacheFileGlob()
    {
        if (empty($this->cacheFileName)) {
            $this->cacheFileName = $this->cacheDirectory . sprintf(self::CACHE_FILE_PATTERN, '*');
        }
        return $this->cacheFileName;
    }
}
