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

use Composer\Autoload\ClassLoader;
use Composer\Composer;
use Composer\Util\Filesystem;

class IncludeFile
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ClassLoader
     */
    private $loader;

    /**
     * Absolute path to include file
     * @var string
     */
    private $includeFile;

    /**
     * Absolute path to include file template
     * @var string
     */
    private $includeFileTemplate;

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Config $config, $loader, $includeFile = '', $includeFileTemplate = '', Filesystem $filesystem = null)
    {
        $this->config = $config;
        $this->loader = $loader;
        $this->includeFile = $includeFile;
        $this->includeFileTemplate = $includeFileTemplate ?: $config->get('include-template-file');
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function dump(): bool
    {
        $this->filesystem->ensureDirectoryExists(dirname($this->includeFile));
        $successfullyWritten = false !== @file_put_contents($this->includeFile, $this->getIncludeFileContent());
        if ($successfullyWritten) {
            // Expose env vars of a possibly available .env file for following composer plugins
            $this->loader->register();
            require $this->includeFile;
            $this->loader->unregister();
        }

        return $successfullyWritten;
    }

    /**
     * Constructs the include file content
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return string
     */
    private function getIncludeFileContent(): string
    {
        if (!file_exists($this->includeFileTemplate)) {
            if (!empty($this->includeFileTemplate)) {
                throw new \RuntimeException('Include file template defined for helhum/dotenv-connector does not exist!', 1581515568);
            }
            // We get here when include file template is empty, which could be a misconfiguration, but more likely happens
            // during plugin package upgrades. In this case we provide the default value for smoother upgrades.
            $this->includeFileTemplate = __DIR__ . '/../res/PHP/dotenv-include.php.tmpl';
        }
        $envFile = $this->config->get('env-file');
        $pathToEnvFileCode = $this->filesystem->findShortestPathCode(
            $this->includeFile,
            $envFile
        );
        $includeFileContent = file_get_contents($this->includeFileTemplate);
        $includeFileContent = $this->replaceToken('env-file', $pathToEnvFileCode, $includeFileContent);

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
    private function replaceToken($name, $content, $subject)
    {
        return str_replace('\'{$' . $name . '}\'', $content, $subject);
    }
}
