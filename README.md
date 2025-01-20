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

## configuration options

Usually you don't need any configuration options. However if you need to, you can
adapt the path or name of the `.env` to fit your requirements.

You configure dotenv connector in the extra section of the root `composer.json` file like that:

```json
  "extra": {
      "helhum/dotenv-connector": {
          "env-file": ".env",
          "adapter": "Helhum\\DotEnvConnector\\Adapter\\SymfonyDotEnv"
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

*The default value* is `"Helhum\DotEnvConnector\Adapter\SymfonyDotEnv"`,
which uses symfony/dotenv default parsing of the one .env file.

This could be useful though e.g. if you prefer to use another dotenv parsing library to expose the variables defined in .env
or you want to switch to another parsing strategy of the Symfony dotenv parsing. In the latter case use
`"Helhum\DotEnvConnector\Adapter\SymfonyLoadEnv"` as value for this option.

To additional support dumped .env settings, use the adapter `"\Helhum\DotEnvConnector\Adapter\SymfonyBootEnv"`.
This allows to load a PHP file instead, compiled from all .env* files in the environment. 
*Important*: The command `dump-env` needed for compiling the PHP file, is not registered by default.
See the section [Configuring Environment Variables in Production](https://symfony.com/doc/current/configuration.html#configuring-environment-variables-in-production)
of the symfony documentation for details.

Have a look at the existing implementations for examples.

## Feedback

Any feedback is appreciated. Please write bug reports, feature request, create pull requests, or just drop me a "thank you" via [Twitter](https://twitter.com/helhum) or spread the word.

Thank you!
