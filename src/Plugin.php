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

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Path to include file relative to vendor dir
     */
    const INCLUDE_FILE = '/helhum/dotenv-include.php';

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
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => ['onPreAutoloadDump'],
        ];
    }

    /**
     * Plugin callback for this script event, which calls the previously implemented static method
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function onPreAutoloadDump()
    {
        $includeFilePath = $this->composer->getConfig()->get('vendor-dir') . self::INCLUDE_FILE;
        $includeFile = new IncludeFile($this->config, $includeFilePath);
        if ($includeFile->dump()) {
            $rootPackage = $this->composer->getPackage();
            $autoloadDefinition = $rootPackage->getAutoload();
            $autoloadDefinition['files'][] = $includeFilePath;
            $rootPackage->setAutoload($autoloadDefinition);
            $this->io->writeError('<info>Registered helhum/dotenv-connector</info>');
        } else {
            $this->io->writeError('<error>Could not dump helhum/dotenv-connector autoload include file</error>');
        }
    }
}
