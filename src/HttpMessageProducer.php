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

use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\ServiceBus\Message\Http\Exception\RuntimeException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use React\Promise\Deferred;
use Throwable;

final class HttpMessageProducer extends AbstractHttpMessageProducer
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(
        HttpClient $httpClient,
        MessageConverter $messageConverter,
        UriInterface $uri,
        RequestFactory $requestFactory = null
    ) {
        $this->httpClient = $httpClient;
        parent::__construct($messageConverter, $uri, $requestFactory);
    }

    protected function handleRequest(RequestInterface $request, Deferred $deferred = null): void
    {
        $response = $this->httpClient->sendRequest($request);

        // we accept only status code 2xx
        if (null === $deferred && '2' !== substr((string) $response->getStatusCode(), 0, 1)) {
            throw RuntimeException::fromResponse($response);
        }

        if ($deferred) {
            try {
                $payload = $this->getPayloadFromResponse($response);
                $deferred->resolve($payload);
            } catch (Throwable $exception) {
                $deferred->reject($exception);
            }
        }
    }
}
