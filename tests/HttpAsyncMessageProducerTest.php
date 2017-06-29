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

namespace ProophTest\ServiceBus;

use Http\Client\HttpAsyncClient;
use Http\Message\RequestFactory;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\MessageDataAssertion;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\Exception\RuntimeException;
use Prooph\ServiceBus\Message\Http\HttpAsyncMessageProducer;
use ProophTest\ServiceBus\Mock\DoSomething;
use ProophTest\ServiceBus\Mock\FetchSomething;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use React\Promise\Deferred;

class HttpAsyncMessageProducerTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $httpClient;

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var ObjectProphecy
     */
    private $request;

    /**
     * @var ObjectProphecy
     */
    private $requestFactory;

    /**
     * @var NoOpMessageConverter
     */
    private $messageConverter;

    /**
     * @var DoSomething
     */
    private $testCommand;

    /**
     * @var FetchSomething
     */
    private $testQuery;

    protected function setUp()
    {
        $this->messageConverter = new NoOpMessageConverter();
        $this->uri = $this->prophesize(UriInterface::class);
        $this->uri = $this->uri->reveal();
        $this->testCommand = new DoSomething(['data' => 'test command']);
        $this->testQuery = new FetchSomething(['some' => 'question']);

        $this->request = $this->prophesize(RequestInterface::class);
        $this->request = $this->request->reveal();

        $this->httpClient = $this->prophesize(HttpAsyncClient::class);
    }

    private function prepareQueryRequest(): void
    {
        $messageData = $this->messageConverter->convertToArray($this->testQuery);
        MessageDataAssertion::assert($messageData);
        $messageData['created_at'] = $this->testQuery->createdAt()->format('Y-m-d\TH:i:s.u');

        $this->requestFactory = $this->prophesize(RequestFactory::class);
        $this->requestFactory
            ->createRequest(
                'POST',
                $this->uri,
                [
                    'Content-Type' => 'application/json',
                ],
                json_encode($messageData)
            )
            ->willReturn($this->request)
            ->shouldBeCalled();
    }

    private function prepareCommandRequest(): void
    {
        $messageData = $this->messageConverter->convertToArray($this->testCommand);
        MessageDataAssertion::assert($messageData);
        $messageData['created_at'] = $this->testCommand->createdAt()->format('Y-m-d\TH:i:s.u');

        $this->requestFactory = $this->prophesize(RequestFactory::class);
        $this->requestFactory
            ->createRequest(
                'POST',
                $this->uri,
                [
                    'Content-Type' => 'application/json',
                ],
                json_encode($messageData)
            )
            ->willReturn($this->request)
            ->shouldBeCalled();
    }

    /**
     * @test
     */
    public function it_sends_message_as_a_http_post_request_to_specified_uri()
    {
        $this->prepareQueryRequest();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn(json_encode(['here\'s' => 'something']))->shouldBeCalled();

        $promise = new FulfilledPromise($response->reveal());

        $this->httpClient->sendAsyncRequest($this->request)->willReturn($promise)->shouldBeCalled();

        $messageProducer = new HttpAsyncMessageProducer(
            $this->httpClient->reveal(),
            $this->messageConverter,
            $this->uri,
            $this->requestFactory->reveal()
        );

        $deferred = new Deferred();
        $messageProducer($this->testQuery, $deferred);

        $deferred->promise()->done(
            function ($result): void {
                $this->assertSame(['here\'s' => 'something'], $result);
            },
            function ($error): void {
                $this->fail('Promise rejected');
            }
        );
    }

    /**
     * @test
     */
    public function it_rejects_deferred_with_exception()
    {
        $this->prepareQueryRequest();

        $promise = new RejectedPromise(new RuntimeException('Invalid JSON Response.'));

        $this->httpClient->sendAsyncRequest($this->request)->willReturn($promise)->shouldBeCalled();

        $messageProducer = new HttpAsyncMessageProducer(
            $this->httpClient->reveal(),
            $this->messageConverter,
            $this->uri,
            $this->requestFactory->reveal()
        );

        $deferred = new Deferred();
        $messageProducer($this->testQuery, $deferred);

        $deferred->promise()->done(
            function ($result): void {
                $this->fail('Promise fulfilled');
            },
            function ($error): void {
                $this->assertInstanceOf(RuntimeException::class, $error);
                $this->assertSame('Invalid JSON Response.', $error->getMessage());
            }
        );
    }

    /**
     * @test
     */
    public function it_rejects_deferred_with_invalid_response()
    {
        $this->prepareQueryRequest();

        $response = $this->prophesize(ResponseInterface::class);
        $response->getBody()->willReturn('invalid')->shouldBeCalled();

        $promise = new FulfilledPromise($response->reveal());

        $this->httpClient->sendAsyncRequest($this->request)->willReturn($promise)->shouldBeCalled();

        $messageProducer = new HttpAsyncMessageProducer(
            $this->httpClient->reveal(),
            $this->messageConverter,
            $this->uri,
            $this->requestFactory->reveal()
        );

        $deferred = new Deferred();
        $messageProducer($this->testQuery, $deferred);

        $deferred->promise()->done(
            function ($result): void {
                $this->fail('Promise fulfilled');
            },
            function ($error): void {
                $this->assertInstanceOf(RuntimeException::class, $error);
                $this->assertSame('Invalid JSON Response.', $error->getMessage());
            }
        );
    }
}
