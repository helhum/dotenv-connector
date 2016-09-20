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
use Composer\Script\Event;
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
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Config
     */
    protected static $config;

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        self::$config = Config::load($io, $composer->getConfig());
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
     * @param Event $event
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public static function onPreAutoloadDump(Event $event)
    {
        $composerConfig = $event->getComposer()->getConfig();
        if (self::$config === null) {
            self::$config = Config::load($event->getIO(), $composerConfig);
        }
        $includeFile = $composerConfig->get('vendor-dir') . self::INCLUDE_FILE;
        $fs = new Filesystem();
        $fs->ensureDirectoryExists(dirname($includeFile));
        $includeFileContent = self::getIncludeFileContent();
        file_put_contents($includeFile, $includeFileContent);

        $composer = $event->getComposer();
        $autoload = $composer->getPackage()->getAutoload();
        $autoload['files'][] = $includeFile;
        $composer->getPackage()->setAutoload($autoload);
    }

    /**
     * Constructs the include file content
     *
     * @return array
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    protected static function getIncludeFileContent()
    {
        $filesystem = new Filesystem();
        $includeFileTemplate = realpath(dirname(__DIR__) . self::INCLUDE_FILE_TEMPLATE);
        $pathToEnvFileCode = $filesystem->findShortestPathCode(
            dirname($includeFileTemplate),
            self::$config->get('env-dir'),
            true
        );
        $cacheDir = self::$config->get('cache-dir');
        $allowOverridesCode = self::$config->get('allow-overrides') ? 'true' : 'false';
        if ($cacheDir === null) {
            $pathToCacheDirCode = 'null';
        } else {
            $cache = new Cache($cacheDir, self::$config->get('env-dir'));
            $cache->cleanCache();
            $pathToCacheDirCode = $filesystem->findShortestPathCode(dirname($includeFileTemplate), $cacheDir, true);
        }
        $includeFileContent = file_get_contents($includeFileTemplate);
        $includeFileContent = self::replaceToken('env-dir', $pathToEnvFileCode, $includeFileContent);
        $includeFileContent = self::replaceToken('allow-overrides', $allowOverridesCode, $includeFileContent);
        $includeFileContent = self::replaceToken('cache-dir', $pathToCacheDirCode, $includeFileContent);

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
    protected static function replaceToken($name, $content, $subject)
    {
        return str_replace('{$' . $name . '}', $content, $subject);
    }
}
