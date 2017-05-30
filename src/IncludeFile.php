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

use Composer\Util\Filesystem;

class IncludeFile
{
    /**
     * @var Config
     */
    private $config;

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

    /**
     * IncludeFile constructor.
     *
     * @param Config $config
     * @param string $includeFile
     * @param string $includeFileTemplate
     * @param Filesystem $filesystem
     */
    public function __construct(Config $config, $includeFile, $includeFileTemplate = '', Filesystem $filesystem = null)
    {
        $this->config = $config;
        $this->includeFile = $includeFile;
        $this->includeFileTemplate = $includeFileTemplate ?: dirname(__DIR__) . '/res/PHP/dotenv-include.php.tmpl';
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    public function dump()
    {
        $this->filesystem->ensureDirectoryExists(dirname($this->includeFile));
        return false !== @file_put_contents($this->includeFile, $this->getIncludeFileContent());
    }

    /**
     * Constructs the include file content
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return string
     */
    private function getIncludeFileContent()
    {
        $envFile = $this->config->get('env-file');
        $pathToEnvFileCode = $this->filesystem->findShortestPathCode(
            dirname($this->includeFile),
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
