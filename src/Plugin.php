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
    const RESOURCES_PATH = '/res/PHP';
    const INCLUDE_FILE = '/dotenv-include.php';
    const INCLUDE_FILE_TEMPLATE = '/dotenv-include.tmpl';

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
     * @param \Composer\Script\Event $event
     * @return bool
     */
    public function onPreAutoloadDump(\Composer\Script\Event $event)
    {
        $includeFile = $this->getResourcesPath() . '/' . self::INCLUDE_FILE;
        $includeFileContent = $this->getIncludeFileContent($event);
        file_put_contents($includeFile, $includeFileContent);
    }

    /**
     * Constructs the include file content
     *
     * @param \Composer\Script\Event $event
     * @return array
     */
    protected function getIncludeFileContent(\Composer\Script\Event $event)
    {
        $filesystem = new Filesystem();
        $includeFileTemplate = realpath($this->getResourcesPath() . '/' . self::INCLUDE_FILE_TEMPLATE);
        $pathToEnvFileCode = $filesystem->findShortestPathCode(
            dirname($includeFileTemplate),
            $this->config->get('env-dir'),
            true
        );
        $cacheDir = $this->config->get('cache-dir');
        $allowOverridesCode = $this->config->get('allow-overrides') ? 'true' : 'false';
        if (($event->isDevMode() && !$this->config->get('cache-in-dev-mode')) || empty($cacheDir) || $cacheDir === $this->config->getBaseDir()) {
            $pathToCacheDirCode = '\'\'';
        } else {
            $pathToCacheDirCode = $filesystem->findShortestPathCode(dirname($includeFileTemplate), $cacheDir, true);
            if (file_exists($cacheDir . DotEnvReader::CACHE_FILE)) {
                @unlink($cacheDir . DotEnvReader::CACHE_FILE);
            }
        }
        $includeFileContent = file_get_contents($includeFileTemplate);
        $includeFileContent = $this->replaceToken('env-dir', $pathToEnvFileCode, $includeFileContent);
        $includeFileContent = $this->replaceToken('allow-overrides', $allowOverridesCode, $includeFileContent);
        $includeFileContent = $this->replaceToken('cache-dir', $pathToCacheDirCode, $includeFileContent);

        return $includeFileContent;
    }

    /**
     * Just returns the resources path
     *
     * @return string
     */
    protected function getResourcesPath()
    {
        return realpath(__DIR__ . '/../' . self::RESOURCES_PATH);
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
        $subject = str_replace('{$' . $name . '}', $content, $subject);
        return $subject;
    }
}
