<?php

/**
 * This file is part of the prooph/psb-http-producer.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\ServiceBus\Message\Http;

use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\RequestFactory;
use Prooph\Common\Messaging\Message;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageDataAssertion;
use Prooph\ServiceBus\Async\MessageProducer;
use Prooph\ServiceBus\Exception\RuntimeException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use React\Promise\Deferred;

abstract class AbstractHttpMessageProducer implements MessageProducer
{
    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var MessageConverter
     */
    protected $messageConverter;

    /**
     * @var UriInterface
     */
    protected $uri;

    public function __construct(
        MessageConverter $messageConverter,
        UriInterface $uri,
        RequestFactory $requestFactory = null
    ) {
        $this->messageConverter = $messageConverter;
        $this->uri = $uri;
        $this->requestFactory = $requestFactory ?: MessageFactoryDiscovery::find();
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(Message $message, Deferred $deferred = null): void
    {
        if ($message->messageType() === Message::TYPE_QUERY && null === $deferred) {
            throw new RuntimeException('Deferred expected for queries');
        } elseif ($message->messageType() !== Message::TYPE_QUERY) {
            $deferred = null;
        }

        $messageData = $this->messageConverter->convertToArray($message);

        MessageDataAssertion::assert($messageData);

        $messageData['created_at'] = $message->createdAt()->format('Y-m-d\TH:i:s.u');

        $request = $this->requestFactory->createRequest(
            'POST',
            $this->uri,
            [
                'Content-Type' => 'application/json',
            ],
            \json_encode($messageData)
        );

        $this->handleRequest($request, $deferred);
    }

    /**
     * @param ResponseInterface $response
     * @return array|mixed
     * @throws RuntimeException
     */
    protected function getPayloadFromResponse(ResponseInterface $response)
    {
        $payload = \json_decode($response->getBody(), true);

        switch (\json_last_error()) {
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

    abstract protected function handleRequest(RequestInterface $request, Deferred $deferred): void;
}
