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
use Helhum\DotEnvConnector\Adapter\SymfonyDotEnv;

class IncludeFile
{
    private const defaultTemplate = __DIR__ . '/../res/PHP/dotenv-include.php.tmpl';

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

    public function __construct(Config $config, ClassLoader $loader, string $includeFile = '', ?string $includeFileTemplate = null, ?Filesystem $filesystem = null)
    {
        $this->config = $config;
        $this->loader = $loader;
        $this->includeFile = $includeFile;
        $this->includeFileTemplate = $includeFileTemplate ?? self::defaultTemplate;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function dump(): bool
    {
        $this->loader->register();
        $this->filesystem->ensureDirectoryExists(dirname($this->includeFile));
        $successfullyWritten = false !== @file_put_contents($this->includeFile, $this->getIncludeFileContent());
        if ($successfullyWritten) {
            // Expose env vars of a possibly available .env file for following composer plugins
            require $this->includeFile;
        }
        $this->loader->unregister();

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
        $envFile = $this->config->get('env-file');
        $adapterClass = $this->config->get('adapter') ?: SymfonyDotEnv::class;
        if (!in_array(DotEnvVars::class ,class_implements($adapterClass), true)) {
            throw new \RuntimeException(sprintf('Adapter "%s" does not implement DotEnvVars interface', $adapterClass), 1598957197);
        }
        $pathToEnvFileCode = $this->filesystem->findShortestPathCode(
            $this->includeFile,
            $envFile
        );
        $includeFileContent = file_get_contents($this->includeFileTemplate);
        $includeFileContent = $this->replaceToken('env-file', $pathToEnvFileCode, $includeFileContent);
        $includeFileContent = $this->replaceToken('adapter', '\\' . $adapterClass . '::class', $includeFileContent);

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
