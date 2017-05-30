# dotenv connector [![Build Status](https://travis-ci.org/helhum/dotenv-connector.svg?branch=master)](https://travis-ci.org/helhum/dotenv-connector)

This is a composer plugin, that makes environment
variables easily available for any composer based project,
without the need to modify the project.

## Background info
You may want to read why it is a good idea to [store config in the environment](http://12factor.net/config).
The idea of [dotenv](http://opensoul.org/2012/07/24/dotenv/) is to make this as easy as possible and this is why
the [phpdotenv](https://github.com/vlucas/phpdotenv) library was created.
phpdotnev loads environment variables from an `.env` file to getenv(), $_ENV and $_SERVER, but you need to
add the parsing code for that yourself.

## composer + phpdotenv + dotenv connector = <3
The idea of this library is, that every composer managed project, a `.env` file (in the same location as your root `composer.json`)
is automatically parsed and loaded, at **composer initialisation time**. This means that the environment variables
are available very early, so that you can use it also during boot time of your application.

Besides that, parsing the `.env` file takes a bit of your request time. Because of that, dotenv connector
can cache the parsed state in a file if a writable cache directory is provided by configuration.

## configuration options

You configure dotenv connector in the extra section of the root `composer.json` file like that:

```
  "extra": {
      "helhum/dotenv-connector": {
          "env-dir": "",
          "cache-dir": "var/cache"
      }
    }
```

#### `env-dir`
You can specify a relative path from the base directory, if you want to put your `.env` file a different location.

*The default value* is "", which means next to your next to your root `composer.json`.


#### `cache-dir`
If you want to make use of the caching feature of this plugin, you must set this value to a valid (and writable) path.
The cache file is written during application runtime (when composer class loader is initialized), **not** during `composer intall`

*The default value* is "" which means no caching is done at all.

## Feedback

Any feedback is appreciated. Please write bug reports, feature request, create pull requests, or just drop me a "thank you" via [Twitter](https://twitter.com/helhum) or spread the word.

Thank you!
