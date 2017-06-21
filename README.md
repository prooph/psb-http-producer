Guzzle HTTP message dispatcher for ProophServiceBus
===================================================

[![Build Status](https://travis-ci.org/prooph/psb-http-producer.svg?branch=master)](https://travis-ci.org/prooph/psb-http-producer)
[![Coverage Status](https://img.shields.io/coveralls/prooph/psb-http-producer.svg)](https://coveralls.io/r/prooph/psb-http-producer?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

Use [php-http/httplug](http://httplug.io/) as message producer for [Prooph Service Bus](https://github.com/prooph/service-bus).
Works together with all bus types: CommandBus, EventBus and QueryBus.

# Installation

You can install the producer via composer by adding `"prooph/psb-http-producer": "^1.0"` as requirement to your composer.json.

Usage
-----

Pass a ready-to-use `Http\Client\HttpClient` to `Prooph\ServiceBus\Message\Http\HttpMessageProducer` together with a `Prooph\Common\Messaging\MessageConverter`.

For async requests use an `Http\Client\HttpAsyncClient` with `\Prooph\ServiceBus\Message\Http\HttpAsyncMessageProducer`.

The MessageProducer sends a POST request to an endpoint specified by a psr-7 URI (using `psr/http-message`) with the json encoded message as body.

An async request means that when the producer is used for querying remote services (QueryBus) the producer does not wait until the promise gets resolved.

For an overview of all ready-to-use http client implementations, check the official documentation of [php-http](http://docs.php-http.org/en/latest/httplug/introduction.html#implementations).

# Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) google group.
- File issues at [https://github.com/prooph/psb-http-producer/issues](https://github.com/prooph/psb-http-producer/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.


# Contribute

Please feel free to fork and extend existing or add new features and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

License
-------

Released under the [New BSD License](LICENSE).
