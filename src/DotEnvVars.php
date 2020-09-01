<?php
declare(strict_types=1);

namespace Helhum\DotEnvConnector;

interface DotEnvVars
{
    public function exposeToEnvironment(string $dotEnvFile): void;
}
