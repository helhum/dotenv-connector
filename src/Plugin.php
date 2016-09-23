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

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

/**
 * Class Plugin
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Path to include file relative to vendor dir
     */
    const INCLUDE_FILE = '/helhum/dotenv-include.php';

    /**
     * Path to include file template relative to package dir
     */
    const INCLUDE_FILE_TEMPLATE = '/res/PHP/dotenv-include.tmpl';

    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->config = Config::load($io, $composer->getConfig());
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::PRE_AUTOLOAD_DUMP => array('onPreAutoloadDump')
        );
    }

    /**
     * Plugin callback for this script event, which calls the previously implemented static method
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function onPreAutoloadDump()
    {
        $composerConfig = $this->composer->getConfig();
        $includeFile = $composerConfig->get('vendor-dir') . self::INCLUDE_FILE;
        $filesystem = new Filesystem();
        $filesystem->ensureDirectoryExists(dirname($includeFile));
        $includeFileContent = $this->getIncludeFileContent($includeFile);
        file_put_contents($includeFile, $includeFileContent);

        $rootPackage = $this->composer->getPackage();
        $autoloadDefinition = $rootPackage->getAutoload();
        $autoloadDefinition['files'][] = $includeFile;
        $rootPackage->setAutoload($autoloadDefinition);
    }

    /**
     * Constructs the include file content
     *
     * @param string $includeFile The path to the file that will be included by composer in autoload.php
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected function getIncludeFileContent($includeFile)
    {
        $filesystem = new Filesystem();
        $includeFileTemplate = dirname(__DIR__) . self::INCLUDE_FILE_TEMPLATE;
        $envDir = $this->config->get('env-dir');
        $pathToEnvFileCode = $filesystem->findShortestPathCode(
            dirname($includeFile),
            $envDir,
            true
        );
        $cacheDir = $this->config->get('cache-dir');
        $allowOverridesCode = $this->config->get('allow-overrides') ? 'true' : 'false';
        if ($cacheDir === null) {
            $pathToCacheDirCode = 'null';
        } else {
            $cache = new Cache($cacheDir, $envDir);
            $cache->cleanCache();
            $pathToCacheDirCode = $filesystem->findShortestPathCode(dirname($includeFile), $cacheDir, true);
        }
        $includeFileContent = file_get_contents($includeFileTemplate);
        $includeFileContent = $this->replaceToken('env-dir', $pathToEnvFileCode, $includeFileContent);
        $includeFileContent = $this->replaceToken('allow-overrides', $allowOverridesCode, $includeFileContent);
        $includeFileContent = $this->replaceToken('cache-dir', $pathToCacheDirCode, $includeFileContent);

        return $includeFileContent;
    }

    /**
     * Replaces a token in the subject (PHP code)
     *
     * @param string $name
     * @param string $content
     * @param string $subject
     * @return string
     */
    protected function replaceToken($name, $content, $subject)
    {
        return str_replace('{$' . $name . '}', $content, $subject);
    }
}
