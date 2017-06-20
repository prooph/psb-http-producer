<?php
/**
 * This file is part of the prooph/psb-http-producer.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\ServiceBus\Message\Http;

use Http\Client\HttpAsyncClient;
use Http\Message\RequestFactory;
use Prooph\Common\Messaging\MessageConverter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use React\Promise\Deferred;

final class HttpAsyncMessageProducer extends AbstractHttpMessageProducer
{
    /**
     * @var HttpAsyncClient
     */
    private $httpAsyncClient;

    public function __construct(
        HttpAsyncClient $httpAsyncClient,
        MessageConverter $messageConverter,
        UriInterface $uri,
        RequestFactory $requestFactory = null
    ) {
        $this->httpAsyncClient = $httpAsyncClient;
        parent::__construct($messageConverter, $uri, $requestFactory);
    }

    protected function handleRequest(RequestInterface $request, Deferred $deferred): void
    {
        $promise = $this->httpAsyncClient->sendAsyncRequest($request);

        $promise->then(
            function (ResponseInterface $response) use ($deferred) {
                try {
                    $payload = $this->getPayloadFromResponse($response);
                    $deferred->resolve($payload);
                } catch (\Throwable $exception) {
                    $deferred->reject($exception);
                }
            },
            function ($reason) use ($deferred) {
                $deferred->reject($reason);
            }
        );
    }
}
