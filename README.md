Guzzle HTTP message dispatcher for ProophServiceBus
===================================================

[![Build Status](https://travis-ci.org/prooph/psb-http-producer.svg?branch=master)](https://travis-ci.org/prooph/psb-http-producer)
[![Coverage Status](https://img.shields.io/coveralls/prooph/psb-http-producer.svg)](https://coveralls.io/r/prooph/psb-http-producer?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

Use [Guzzle](http://guzzlephp.org/) 6+ as message producer for [Prooph Service Bus](https://github.com/prooph/service-bus).
Works together with all bus types: CommandBus, EventBus and QueryBus.

# Installation

You can install the producer via composer by adding `"prooph/psb-http-producer": "~0.4"` as requirement to your composer.json.

Usage
-----

Pass a ready-to-use `GuzzleHttp\Client` to `Prooph\ServiceBus\Message\Http\HttpMessageProducer` together with a `Prooph\Common\Messaging\MessageConverter`.

The HttpMessageProducer sends a POST request to `<URL>/api/messages` with the json encoded message as body.
You should set the base url, host, port, etc. as default options for the guzzle client.

If your http endpoint uses another resource than `/api/messages` you can override the default by passing the resource uri as third argument to
`Prooph\ServiceBus\Message\Http\HttpMessageProducer`.

With the fourth argument you can enable the async mode.
This means that when the producer is used for querying remote services (QueryBus) the producer does not wait until the Guzzle\Promise gets resolved.
Instead it attaches own handlers and resolves or rejects the QueryBus deferred when the Guzzle\Promise is resolved or rejected
(see [Guzzle Promises docs](https://github.com/guzzle/promises/blob/master/README.md) for details about async promise handling)

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
