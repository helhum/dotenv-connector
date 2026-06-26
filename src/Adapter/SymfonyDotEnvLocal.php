<?php
declare(strict_types=1);

namespace Helhum\DotEnvConnector\Adapter;

use Helhum\DotEnvConnector\DotEnvVars;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Loads ".env" and then ".env.local" on top of it, so shared defaults can live in
 * ".env" (committed) while per-instance overrides live in ".env.local" (git-ignored).
 *
 * Values from ".env.local" override those from ".env". Neither overrides variables
 * already present in the real environment, unless DOTENV_CONNECTOR_OVERRIDE is set.
 *
 * Like {@see SymfonyDotEnv} this does nothing when APP_ENV is set, so the
 * "real environment variables are already provided" production workflow is preserved.
 * Unlike {@see SymfonyLoadEnv} it does NOT load per-environment files
 * (".env.$APP_ENV", ".env.$APP_ENV.local", ".env.dist") and never writes APP_ENV —
 * keeping the model to exactly two files for projects (e.g. TYPO3) that have no
 * notion of an APP_ENV cascade.
 */
class SymfonyDotEnvLocal implements DotEnvVars
{
    public function exposeToEnvironment(string $dotEnvFile): void
    {
        if (getenv('APP_ENV')) {
            return;
        }

        $dotEnv = new Dotenv();
        $dotEnv->usePutenv();
        $override = (bool)getenv('DOTENV_CONNECTOR_OVERRIDE');

        foreach ([$dotEnvFile, $dotEnvFile . '.local'] as $file) {
            if (!file_exists($file)) {
                continue;
            }
            if ($override) {
                $dotEnv->overload($file);
            } else {
                $dotEnv->load($file);
            }
        }
    }
}
