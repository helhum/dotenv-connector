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

class Config
{
    const RELATIVE_PATHS = 1;

    /**
     * @var array
     */
    public static $defaultConfig = [
        'env-dir' => '',
        'cache-dir' => null,
        'allow-overrides' => true,
    ];

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $baseDir;

    /**
     * @param string $baseDir
     */
    public function __construct($baseDir = null)
    {
        $this->baseDir = $baseDir;
        // load defaults
        $this->config = static::$defaultConfig;
    }

    /**
     * Merges new config values with the existing ones (overriding)
     *
     * @param array $config
     */
    public function merge($config)
    {
        // override defaults with given config
        if (!empty($config['extra']['helhum/dotenv-connector']) && is_array($config['extra']['helhum/dotenv-connector'])) {
            foreach ($config['extra']['helhum/dotenv-connector'] as $key => $val) {
                $this->config[$key] = $val;
            }
        }
    }

    /**
     * Returns a setting
     *
     * @param  string $key
     * @param  int $flags Options (see class constants)
     * @throws \RuntimeException
     * @return mixed
     */
    public function get($key, $flags = 0)
    {
        if (!isset($this->config[$key])) {
            return null;
        }
        switch ($key) {
            case 'env-dir':
            case 'cache-dir':
                $val = rtrim($this->process($this->config[$key], $flags), '/\\');
                return ($flags & self::RELATIVE_PATHS == 1) ? $val : $this->realpath($val);
            default:
                return $this->process($this->config[$key], $flags);
        }
    }

    /**
     * @param int $flags Options (see class constants)
     * @return array
     */
    public function all($flags = 0)
    {
        $all = [];
        foreach ($this->config as $key => $_) {
            $all['config'][$key] = $this->get($key, $flags);
        }

        return $all;
    }

    /**
     * @return array
     */
    public function raw()
    {
        return [
            'config' => $this->config,
        ];
    }

    /**
     * Checks whether a setting exists
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * Replaces {$refs} inside a config string
     *
     * @param  string $value a config string that can contain {$refs-to-other-config}
     * @param  int $flags Options (see class constants)
     * @return string
     */
    protected function process($value, $flags)
    {
        $config = $this;

        if (!is_string($value)) {
            return $value;
        }

        return preg_replace_callback('#\{\$(.+)\}#',
            function ($match) use ($config, $flags) {
                return $config->get($match[1], $flags);
            },
            $value);
    }

    /**
     * Turns relative paths in absolute paths without realpath()
     *
     * Since the dirs might not exist yet we can not call realpath or it will fail.
     *
     * @param  string $path
     * @return string
     */
    protected function realpath($path)
    {
        if ($path === '') {
            return $this->baseDir;
        }

        if ($path[0] === '/' || $path[1] === ':') {
            return $path;
        }

        return $this->baseDir . '/' . $path;
    }

    /**
     * @return string
     */
    public function getBaseDir()
    {
        return $this->baseDir;
    }

    /**
     * @param \Composer\IO\IOInterface $io
     * @param \Composer\Config $composerConfig
     * @return Config
     */
    public static function load(\Composer\IO\IOInterface $io, \Composer\Config $composerConfig)
    {
        static $config;
        if ($config === null) {
            $baseDir = realpath(substr($composerConfig->get('vendor-dir'), 0, -strlen($composerConfig->get('vendor-dir', self::RELATIVE_PATHS))));
            $localConfig = \Composer\Factory::getComposerFile();
            $file = new \Composer\Json\JsonFile($localConfig, new \Composer\Util\RemoteFilesystem($io));

            $config = new static($baseDir);
            $config->merge($file->read());
        }
        return $config;
    }
}
