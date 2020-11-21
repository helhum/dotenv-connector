<?php
declare(strict_types=1);

namespace Helhum\DotEnvConnector\Adapter;

use Helhum\DotEnvConnector\DotEnvVars;
use Symfony\Component\Dotenv\Dotenv;

class SymfonyLoadEnv implements DotEnvVars
{
    public function exposeToEnvironment(string $dotEnvFile): void
    {
        if (is_file($dotEnvFile) || is_file("$dotEnvFile.dist")) {
            $dotEnv = new Dotenv();
            $dotEnv->usePutenv();
            $dotEnv->loadEnv($dotEnvFile);
        }
    }
}
