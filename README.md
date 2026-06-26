# dotenv connector [![Build Status](https://travis-ci.org/helhum/dotenv-connector.svg?branch=master)](https://travis-ci.org/helhum/dotenv-connector)

This is a composer plugin, that makes environment
variables from a .env file available for any composer based project,
without the need to modify code in the project.

## Background info
You may want to read why it is a good idea to [store config in the environment](http://12factor.net/config).
The idea of [dotenv](http://opensoul.org/2012/07/24/dotenv/) is to make this as easy as possible and this is why
the [phpdotenv](https://github.com/vlucas/phpdotenv) library was created.
phpdotenv loads environment variables from an `.env` file to getenv(), $_ENV and $_SERVER, but you need to
add the parsing code for that yourself.

## composer + symfony/dotenv + dotenv connector = <3
The idea of this library is, that every composer managed project, a `.env` file (in the same location as your root `composer.json`)
is automatically parsed and loaded, at **composer autoload initialisation time**. This means that the environment variables
are available very early, so that you can use it also during boot time of your application.

If the environment variable `APP_ENV` is set to any value, or the specified `.env` file does not
exist, no operation is performed, so that you can safely require this package for production.

If you have the possibility to expose environment variables in a production environment, it is recommended
to do so and also set `APP_ENV` and use the variables that are directly exposed in the environment.

However for smaller scale projects it is still a valid and easy solution to use a `.env` file
also for production environments.

You can set the environment variable `DOTENV_CONNECTOR_OVERRIDE` to enable overriding of existing environment variables with the values of the `.env` file.

## configuration options

Usually you don't need any configuration options. However if you need to, you can
adapt the path or name of the `.env` to fit your requirements.

You configure dotenv connector in the extra section of the root `composer.json` file like that:

```json
  "extra": {
      "helhum/dotenv-connector": {
          "env-file": ".env",
          "adapter": "Helhum\\DotEnvConnector\\Adapter\\SymfonyDotEnvLocal"
      }
    }
```

#### `env-file`
You can specify a relative path from the base directory, if you want to put your `.env` file a different location.

*The default value* is ".env", which means next to your root `composer.json`.

##### Side note for quoting values in the `.env` file
As the `.env` file parsing behaves like if it was included in a shell,
you have to be aware of that values with literal `$` signs
need to be enclosed in single quotes.
This may be the case if you use hashed values of credentials you pass via `.env`, for example.

#### `adapter`
You can specify a class that implements `\Helhum\DotEnvConnector\DotEnvVars` interface,
if you need a different way to expose env vars.

*The default value* is "Helhum\DotEnvConnector\Adapter\SymfonyDotEnvLocal",
which loads `.env` and then `.env.local` on top of it (see below).

> **Upgrading from 3.x:** the default adapter changed from `SymfonyDotEnv` (which loads a single
> `.env` file) to `SymfonyDotEnvLocal` (which also loads `.env.local`). If a project has a
> `.env.local` file, it is now loaded automatically. To keep the previous behaviour, explicitly set
> the adapter to `Helhum\DotEnvConnector\Adapter\SymfonyDotEnv`.

You may set this to any class implementing the `DotEnvVars` interface if you prefer another parsing
strategy or another dotenv parsing library.

Bundled adapters:

* `Helhum\DotEnvConnector\Adapter\SymfonyDotEnvLocal` *(default)* — loads `.env` and then
  `.env.local` on top of it, so shared defaults can live in `.env` (committed) and per-instance
  overrides in `.env.local` (git-ignored). Values in `.env.local` override those from `.env`; neither
  overrides variables already present in the real environment unless `DOTENV_CONNECTOR_OVERRIDE` is
  set. It does nothing when `APP_ENV` is set, and — unlike `SymfonyLoadEnv` — it does not load
  per-environment files (`.env.$APP_ENV` etc.) and never writes `APP_ENV`. This keeps the model to
  exactly two files for projects (e.g. TYPO3) that have no notion of an `APP_ENV` cascade.
* `Helhum\DotEnvConnector\Adapter\SymfonyDotEnv` — the previous default; loads a single `.env` file
  only, using symfony/dotenv's default parsing. Set the adapter to this to restore 3.x behaviour.
* `Helhum\DotEnvConnector\Adapter\SymfonyLoadEnv` — uses Symfony's `loadEnv()`, loading the full
  Symfony cascade: `.env`, `.env.local`, `.env.$APP_ENV` and `.env.$APP_ENV.local` (and a `.env.dist`
  fallback). `APP_ENV` selects which environment files are layered on and defaults to `dev`.

##### Why `SymfonyDotEnvLocal` and not just `SymfonyLoadEnv`?

`SymfonyLoadEnv` already loads `.env.local` — but only as one step of Symfony's full environment
cascade, which is keyed on `APP_ENV`: it also pulls in `.env.$APP_ENV` / `.env.$APP_ENV.local`,
defaults `APP_ENV` to `dev`, and writes that value back into the environment. That is the right
behaviour inside a Symfony application, where `APP_ENV` is the central environment switch.

Most consumers of this package, however, only want the simple "shared `.env`, overridden per
instance by `.env.local`" pattern and have no `APP_ENV` notion at all — TYPO3, for instance, keys
its environment off `TYPO3_CONTEXT` and never reads `APP_ENV`. For them the cascade is conceptual
overhead with surprising side effects (a stray `.env.dev` suddenly contributing values, an
unexpected `APP_ENV` appearing in the environment).

`SymfonyDotEnvLocal` exists to cover exactly that two-file case and nothing more. Crucially it also
preserves `APP_ENV`'s meaning *as it already works in this package* — a kill switch that skips
dotenv parsing entirely (e.g. in production, where real environment variables are provided) — rather
than repurposing `APP_ENV` as a file selector the way `loadEnv()` does. That made it a safe choice
for the default: the common case works out of the box, while the only behavioural change from the
previous default is that an existing `.env.local` is now loaded.

Have a look at the existing implementations for examples.

## Feedback

Any feedback is appreciated. Please write bug reports, feature request, create pull requests, or just drop me a "thank you" via [Twitter](https://twitter.com/helhum) or spread the word.

Thank you!
