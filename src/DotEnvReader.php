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
 * Class DotEnvReader
 */
class DotEnvReader
{
    const CACHE_FILE = '/dotenv-cache.php';

    /**
     * Absolute path to the .env file
     *
     * @var string
     */
    protected $dotEnvPath;

    /**
     * Absolute path to a (writable) cache directory (or empty for disabled cache)
     *
     * @var string
     */
    protected $cacheDirectory;

    /**
     * Whether or not it is allowed to override existing environment vars
     *
     * @var bool
     */
    protected $allowOverloading = true;

    /**
     * DotEnvReader constructor.
     *
     * @param string $dotEnvPath
     * @param bool $allowOverloading
     * @param string $cacheDirectory
     */
    public function __construct($dotEnvPath, $allowOverloading = true, $cacheDirectory = '')
    {
        $this->dotEnvPath = $dotEnvPath;
        $this->allowOverloading = $allowOverloading;
        $this->cacheDirectory = $cacheDirectory;
    }

    /**
     * Reads the environment file either by parsing it directly or from a cached file
     */
    public function read()
    {
        if (!empty($this->cacheDirectory)
            && @file_exists($this->cacheDirectory . self::CACHE_FILE)
        ) {
            require $this->cacheDirectory . self::CACHE_FILE;
            return;
        }
        if (!empty($this->cacheDirectory)
            && is_dir($this->cacheDirectory)
            && is_writable($this->cacheDirectory)
        ) {
            $superGlobalEnvBackup = $_ENV;
            $this->parseEnvironmentVariables();
            $writtenEnvVars = array_diff_assoc($_ENV, $superGlobalEnvBackup);
            file_put_contents($this->cacheDirectory . self::CACHE_FILE, $this->getCachedCode($writtenEnvVars));
        } else {
            $this->parseEnvironmentVariables();
        }
        return;
    }

    /**
     * Parses environment file
     */
    protected function parseEnvironmentVariables()
    {
        $dotEnvConnector = new \Dotenv\Dotenv($this->dotEnvPath);
        if ($this->allowOverloading) {
            $dotEnvConnector->overload();
        } else {
            $dotEnvConnector->load();
        }
    }

    /**
     * Creates the code for the cached environment file
     *
     * @param array $writtenEnvVars
     * @return string
     */
    protected function getCachedCode(array $writtenEnvVars)
    {
        $cacheFileContent = "<?php\n";
        foreach ($writtenEnvVars as $name => $value) {
            $cacheFileContent .= "putenv('$name=$value');\n";
            $cacheFileContent .= "\$_ENV['$name'] = '$value';\n";
            $cacheFileContent .= "\$_SERVER['$name'] = '$value';\n";
        }
        return $cacheFileContent;
    }
}
