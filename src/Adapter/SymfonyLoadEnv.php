<?php
declare(strict_types=1);
namespace Helhum\DotEnvConnector\Adapter;

use Helhum\DotEnvConnector\DotEnvVars;
use Symfony\Component\Dotenv\Dotenv;

class SymfonyLoadEnv implements DotEnvVars
{
    public function exposeToEnvironment(string $dotEnvFile): void
    {
        if (file_exists($dotEnvFile)) {
            $dotEnv = new Dotenv();
            $dotEnv->usePutenv();
            $dotEnv->loadEnv($dotEnvFile);
        }
    }
}
