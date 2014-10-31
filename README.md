Guzzle HTTP message dispatcher for ProophServiceBus
===================================================

[![Build Status](https://travis-ci.org/prooph/psb-http-dispatcher.svg?branch=master)](https://travis-ci.org/prooph/psb-http-dispatcher)

Use [Guzzle](http://guzzlephp.org/) as message dispatcher for [ProophServiceBus](https://github.com/prooph/service-bus).

# Installation

You can install the dispatcher via composer by adding `"prooph/psb-http-dispatcher": "~0.1"` as requirement to your composer.json.

Usage
-----

Pass a ready-to-use `GuzzleHttp\Client` to `Prooph\ServiceBus\Message\Http\MessageDispatcher` and you are done.
The MessageDispatcher sends a POST request to `<URL>/api/messages` with the json encoded message as body.
You should set the base url, host, port, etc. as default options for the guzzle client. If your http endpoint uses
another resource than `/api/messages` you can override the default by passing the resource as second argument to
`Prooph\ServiceBus\Message\Http\MessageDispatcher`. With the third argument of `Prooph\ServiceBus\Message\Http\MessageDispatcher`
you can enable the future mode available since Guzzle 5.0+ (see [Guzzle docs](http://docs.guzzlephp.org/en/latest/clients.html#asynchronous-requests) for details)

# Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) google group.
- File issues at [https://github.com/prooph/psb-http-dispatcher/issues](https://github.com/prooph/psb-http-dispatcher/issues).

# Contribute

Please feel free to fork and extend existing or add new features and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

License
-------

Released under the [New BSD License](https://github.com/prooph/psb-http-dispatcher/blob/master/LICENSE).
