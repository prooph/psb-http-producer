<?php

declare(strict_types=1);
/**
 * This file is part of the prooph/psb-http-producer.
 * (c) 2014-2017 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Prooph\ServiceBus\Message\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageDataAssertion;
use Prooph\ServiceBus\Async\MessageProducer;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Prooph\ServiceBus\Exception\RuntimeException;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;

/**
 * Class HttpMessageProducer
 *
 * Uses an already configured GuzzleHttp\Client to send a post request to the specified URI (defaults to /api/messages)
 * with a json encoded message in the request body.
 *
 * @package Prooph\ServiceBus\Message\Http
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class HttpMessageProducer implements MessageProducer
{
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var MessageConverter
     */
    private $messageConverter;

    /**
     * @var string
     */
    private $uri = '/api/messages';

    /**
     * @var bool
     */
    private $async;

    /**
     * @param Client $guzzleClient
     * @param MessageConverter $messageConverter
     * @param null|string $uri
     * @param bool $async
     */
    public function __construct(Client $guzzleClient, MessageConverter $messageConverter, string $uri = null, bool $async = false)
    {
        if (null !== $uri) {
            $this->useUri($uri);
        }

        $this->guzzleClient = $guzzleClient;
        $this->messageConverter = $messageConverter;
        $this->async = $async;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $message, Deferred $deferred = null): void
    {
        $messageData = $this->messageConverter->convertToArray($message);

        MessageDataAssertion::assert($messageData);

        $messageData['created_at'] = $message->createdAt()->format('Y-m-d\TH:i:s.u');

        $promise = $this->guzzleClient->postAsync($this->uri, ['json' => $messageData]);

        if ($deferred) {
            if ($this->async) {
                $this->resolveOrRejectDeferredAsync($deferred, $promise);
            } else {
                $this->resolveOrRejectDeferredSync($deferred, $promise);
            }
        }
    }

    /**
     * @param string $uri starting with a slash
     * @throws InvalidArgumentException if $uri is not a string or if it does not start with a slash
     */
    public function useUri(string $uri): void
    {
        if ($uri === '') {
            throw new InvalidArgumentException('Uri must be a non empty string');
        }

        if ($uri[0] !== '/') {
            throw new InvalidArgumentException('Wrong URI provided: ' . $uri . '. It must start with a slash!');
        }

        $this->uri = $uri;
    }

    /**
     * @param ResponseInterface $response
     * @return array|mixed
     * @throws RuntimeException
     */
    private function getPayloadFromResponse(ResponseInterface $response)
    {
        $payload = json_decode($response->getBody(), true);

        switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
                throw new RuntimeException('Invalid JSON Response, maximum stack depth exceeded.');
            case JSON_ERROR_UTF8:
                throw new RuntimeException('Malformed UTF-8 characters in JSON Response, possibly incorrectly encoded.');
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_STATE_MISMATCH:
                throw new RuntimeException('Invalid JSON Response.');
        }

        return null === $payload ? [] : $payload;
    }

    /**
     * This method waits for the promise to resolve and then proxies the result to the deferred
     *
     * @param Deferred $deferred
     * @param PromiseInterface $promise
     */
    private function resolveOrRejectDeferredSync(Deferred $deferred, PromiseInterface $promise): void
    {
        try {
            $response = $promise->wait();
            $payload = $this->getPayloadFromResponse($response);
            $deferred->resolve($payload);
        } catch (\Exception $ex) {
            $deferred->reject($ex);
        }
    }

    /**
     * This method registers an onFulfilled and an onRejected handler to proxy the result to the deferred
     *
     * Note: The Guzzle\Promise will only be resolved when the global task queue `run` is invoked:
     * This task queue MUST be run in an event loop in order for promises to be
     * settled asynchronously.
     *
     * <code>
     * while ($eventLoop->isRunning()) {
     *     GuzzleHttp\Promise\queue()->run();
     * }
     * </code>
     *
     * @param Deferred $deferred
     * @param PromiseInterface $promise
     */
    private function resolveOrRejectDeferredAsync(Deferred $deferred, PromiseInterface $promise): void
    {
        $promise->then(
            function (ResponseInterface $response) use ($deferred) {
                try {
                    $payload = $this->getPayloadFromResponse($response);
                    $deferred->resolve($payload);
                } catch (\Exception $e) {
                    $deferred->reject($e);
                }
            },
            function ($reason) use ($deferred) {
                $deferred->reject($reason);
            }
        );
    }
}
