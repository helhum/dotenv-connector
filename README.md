# dotenv connector [![Build Status](https://travis-ci.org/helhum/dotenv-connector.svg?branch=master)](https://travis-ci.org/helhum/dotenv-connector)

TRAVIS TEST

This is a composer plugin, that makes environment
variables from a .env file available for any composer based project,
without the need to modify code in the project.

## Background info
You may want to read why it is a good idea to [store config in the environment](http://12factor.net/config).
The idea of [dotenv](http://opensoul.org/2012/07/24/dotenv/) is to make this as easy as possible and this is why
the [phpdotenv](https://github.com/vlucas/phpdotenv) library was created.
phpdotnev loads environment variables from an `.env` file to getenv(), $_ENV and $_SERVER, but you need to
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
          "include-template-file": "{$vendor-dir}/helhum/dotenv-connector/res/PHP/dotenv-include.php.tmpl"
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

#### `include-template-file`
You can specify a relative path from the base directory,
if you need a different template for the to be created include file.

*The default value* is "{$vendor-dir}/helhum/dotenv-connector/res/PHP/dotenv-include.php.tmpl", which uses the
default template, which uses symfony/dotenv default parsing of the one .env file.
Change this option with great care and at your own risk.
This could be useful though e.g. if you prefer to use another dotenv parsing library to expose the variables defined in .env
or you want to switch to another parsing strategy of the Symfony dotenv parsing. In the latter case use
"{$vendor-dir}/helhum/dotenv-connector/res/PHP/dotenv-include-sf-loadenv.php.tmpl" as value for this option.
Have a look at the existing template files for examples for your own files in case you need them.

## Feedback

Any feedback is appreciated. Please write bug reports, feature request, create pull requests, or just drop me a "thank you" via [Twitter](https://twitter.com/helhum) or spread the word.

Thank you!
