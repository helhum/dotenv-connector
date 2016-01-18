<?php
namespace Helhum\DotEnvConnector;

/*
 * This file is part of the class alias loader package.
 *
 * (c) Helmut Hummel <info@helhum.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Composer\Composer;
use Composer\Config;
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
     * @var Composer
     */
    protected $composer;

    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var string
     */
    protected $baseDir;

    /**
     * Apply plugin modifications to composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->baseDir = substr($this->composer->getConfig()->get('vendor-dir'), 0, -strlen($this->composer->getConfig()->get('vendor-dir', Config::RELATIVE_PATHS)));
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents() {
        return array(
            ScriptEvents::POST_AUTOLOAD_DUMP => array('onPostAutoloadDump')
        );
    }

    /**
     * Plugin callback for this script event, which calls the previously implemented static method
     *
     * @param \Composer\Script\Event $event
     * @return bool
     */
    public function onPostAutoloadDump(\Composer\Script\Event $event)
    {
        if (!file_exists($this->baseDir . '.env')) {
            return;
        }

        if ($event->isDevMode()) {
            $filesystem = new Filesystem();
            $pathToRootCode = $filesystem->findShortestPathCode($this->composer->getConfig()->get('vendor-dir') . '/foo', $this->baseDir);
            $envCode = <<<EOF
\$dec = new \\Dotenv\\Dotenv($pathToRootCode);
\$dec->overload();
unset(\$dec);
EOF;
        } else {
            $envCode = $this->getCachedCode($this->baseDir);
        }
        $code = <<<EOF
// start insert by helhum/dotenv-connector
$envCode
// end by helhum/dotenv-connector
EOF;
        $this->insertCode("\n" . $code . "\n\n");
    }

    /**
     * @param string $code
     */
    protected function insertCode($code) {
        $composerConfig = $this->composer->getConfig();
        $vendorDir = $composerConfig->get('vendor-dir');
        $autoloadFile = $vendorDir . '/autoload.php';
        if (!file_exists($autoloadFile)) {
            throw new \RuntimeException(sprintf(
                'Could not adjust autoloader: The file %s was not found.',
                $autoloadFile
            ),
            1453127591
            );
        }

        $this->io->write('<info>Inserting dotenv initialization into main autoload file</info>');

        $originalAutoloadFileContent = file_get_contents($autoloadFile);
        preg_match('/\nreturn ([^;]+;\s*$)/mD', $originalAutoloadFileContent, $matches);
        if (empty($matches[1])) {
            throw new \RuntimeException(
                'Could not adjust autoloader: autoload.php could not be parsed!',
                1453127591
            );
        }
        $code = $matches[1] . "\n" . $code;
        // Regex modifiers:
        // "m": \s matches newlines
        // "D": $ matches at EOF only
        // Translation: insert before the last "return" in the file
        $contents = preg_replace('/\n(?=return [^;]+;\s*$)/mD', "\n" . $code, $originalAutoloadFileContent);
        file_put_contents($autoloadFile, $contents);
    }

    /**
     * @param string $envFileDir
     * @return string
     */
    protected function getCachedCode($envFileDir)
    {
        $superGlobalEnvBackup = $_ENV;
        $dotEnv = new \Dotenv\Dotenv($envFileDir);
        $dotEnv->overload();
        $writtenEnvVars = array_diff_assoc($_ENV, $superGlobalEnvBackup);
        $cacheFileContent = "\n";
        foreach ($writtenEnvVars as $name => $value) {
            $cacheFileContent .= "putenv('$name=$value');\n";
            $cacheFileContent .= "\$_ENV['$name'] = '$value';\n";
            $cacheFileContent .= "\$_SERVER['$name'] = '$value';\n";
        }
        return $cacheFileContent;
    }
}
