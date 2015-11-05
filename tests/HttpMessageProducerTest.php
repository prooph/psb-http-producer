<?php
/*
 * This file is part of the prooph/psb-http-producer.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 10/31/14 - 09:57 PM
 */
namespace ProophTest\ServiceBus;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Promise\Promise;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\Exception\RuntimeException;
use Prooph\ServiceBus\Message\Http\HttpMessageProducer;
use ProophTest\ServiceBus\Mock\DoSomething;
use ProophTest\ServiceBus\Mock\FetchSomething;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;

/**
 * Class HttpMessageProducerTest
 *
 * @package Prooph\ServiceBusTest
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class HttpMessageProducerTest extends TestCase
{
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @var Promise
     */
    private $responsePromise;

    /**
     * @var HttpMessageProducer
     */
    private $httpMessageProducer;

    protected function setUp()
    {
        $this->responsePromise = new Promise();

        $this->mockHandler = new MockHandler([]);

        $this->mockHandler->append($this->responsePromise);

        $this->guzzleClient = new Client(['handler' => $this->mockHandler, 'base_uri' => 'http://localhost:8080/']);

        $this->httpMessageProducer = new HttpMessageProducer($this->guzzleClient, new NoOpMessageConverter());
    }

    /**
     * @test
     */
    public function it_sends_message_as_a_http_post_request_to_specified_uri()
    {
        $doSomething = new DoSomething(["data" => "test command"]);

        $messageProducer = $this->httpMessageProducer;

        $messageProducer->useUri('/test-api/messages');

        $messageProducer($doSomething);

        $this->assertNotNull($this->mockHandler->getLastRequest());

        $request = $this->mockHandler->getLastRequest();

        $messageArr = json_decode($request->getBody(), true);

        $doSomethingArr = $doSomething->toArray();
        $doSomethingArr['created_at'] = $doSomething->createdAt()->format('Y-m-d\TH:i:s.u');

        $this->assertEquals($doSomethingArr, $messageArr);

        $this->assertEquals('http://localhost:8080/test-api/messages', $request->getUri());
    }

    /**
     * @test
     */
    public function it_uses_default_uri_when_non_is_specified()
    {
        $doSomething = new DoSomething(["data" => "test command"]);

        $messageProducer = $this->httpMessageProducer;

        $messageProducer($doSomething);

        $this->assertNotNull($this->mockHandler->getLastRequest());

        $request = $this->mockHandler->getLastRequest();

        $messageArr = json_decode($request->getBody(), true);

        $doSomethingArr = $doSomething->toArray();
        $doSomethingArr['created_at'] = $doSomething->createdAt()->format('Y-m-d\TH:i:s.u');

        $this->assertEquals($doSomethingArr, $messageArr);

        $this->assertEquals('http://localhost:8080/api/messages', $request->getUri());
    }

    /**
     * @test
     */
    public function it_resolves_deferred_with_json_response_synchronous()
    {
        $fetchDataQuery = new FetchSomething(['filter' => 'foo']);

        $psrResponse = $this->prophesize(ResponseInterface::class);

        $psrResponse->getBody()->willReturn(json_encode(['data' => 'bar']));

        $this->responsePromise->resolve($psrResponse->reveal());

        $messageProducer = $this->httpMessageProducer;

        $queryDeferred = new Deferred();

        $messageProducer($fetchDataQuery, $queryDeferred);

        $responseData = null;

        $queryDeferred->promise()->done(function ($data) use (&$responseData) {
            $responseData = $data;
        });

        $this->assertEquals(['data' => 'bar'], $responseData);
    }

    /**
     * @test
     */
    public function it_rejects_deferred_with_exception_synchronous()
    {
        $fetchDataQuery = new FetchSomething(['filter' => 'foo']);

        $ex = new \Exception('ka boom');

        $this->responsePromise->reject($ex);

        $messageProducer = $this->httpMessageProducer;

        $queryDeferred = new Deferred();

        $messageProducer($fetchDataQuery, $queryDeferred);

        $rejectionReason = null;

        $queryDeferred->promise()->otherwise(function ($reason) use (&$rejectionReason) {
            $rejectionReason = $reason;
        });

        $this->assertSame($ex, $rejectionReason);
    }

    /**
     * @test
     */
    public function it_rejects_deferred_with_runtime_exception_synchronous_when_json_response_is_invalid()
    {
        $fetchDataQuery = new FetchSomething(['filter' => 'foo']);

        $psrResponse = $this->prophesize(ResponseInterface::class);

        //Return invalid json
        $psrResponse->getBody()->willReturn('[{"data" => "bar"');

        $this->responsePromise->resolve($psrResponse->reveal());

        $messageProducer = $this->httpMessageProducer;

        $queryDeferred = new Deferred();

        $messageProducer($fetchDataQuery, $queryDeferred);

        $rejectionReason = null;

        $queryDeferred->promise()->otherwise(function ($reason) use (&$rejectionReason) {
            $rejectionReason = $reason;
        });

        $this->assertInstanceOf(RuntimeException::class, $rejectionReason);
    }

    /**
     * @test
     */
    public function it_resolves_deferred_with_json_response_asynchronous()
    {
        $producerInAsyncMode = new HttpMessageProducer($this->guzzleClient, new NoOpMessageConverter(), null, true);

        $fetchDataQuery = new FetchSomething(['filter' => 'foo']);

        $psrResponse = $this->prophesize(ResponseInterface::class);

        $psrResponse->getBody()->willReturn(json_encode(['data' => 'bar']));

        $queryDeferred = new Deferred();

        $producerInAsyncMode($fetchDataQuery, $queryDeferred);

        $this->responsePromise->resolve($psrResponse->reveal());

        //Perform next tick, required to resolve the promise when we are in async mode
        $queue = \GuzzleHttp\Promise\queue();
        $queue->run();

        $responseData = null;

        $queryDeferred->promise()->done(function ($data) use (&$responseData) {
            $responseData = $data;
        });

        $this->assertEquals(['data' => 'bar'], $responseData);
    }

    /**
     * @test
     */
    public function it_rejects_deferred_with_json_response_asynchronous()
    {
        $producerInAsyncMode = new HttpMessageProducer($this->guzzleClient, new NoOpMessageConverter(), null, true);

        $fetchDataQuery = new FetchSomething(['filter' => 'foo']);

        $ex = new \Exception('ka boom');

        $queryDeferred = new Deferred();

        $producerInAsyncMode($fetchDataQuery, $queryDeferred);

        $this->responsePromise->reject($ex);

        //Perform next tick, required to resolve the promise when we are in async mode
        $queue = \GuzzleHttp\Promise\queue();
        $queue->run();

        $rejectionReason = null;

        $queryDeferred->promise()->otherwise(function ($reason) use (&$rejectionReason) {
            $rejectionReason = $reason;
        });

        $this->assertSame($ex, $rejectionReason);
    }

    /**
     * @test
     */
    public function it_rejects_deferred_with_runtime_exception_asynchronous_when_json_response_is_invalid()
    {
        $producerInAsyncMode = new HttpMessageProducer($this->guzzleClient, new NoOpMessageConverter(), null, true);

        $fetchDataQuery = new FetchSomething(['filter' => 'foo']);

        $psrResponse = $this->prophesize(ResponseInterface::class);

        $queryDeferred = new Deferred();

        $producerInAsyncMode($fetchDataQuery, $queryDeferred);

        //Return invalid json
        $psrResponse->getBody()->willReturn('[{"data" => "bar"');

        $this->responsePromise->resolve($psrResponse->reveal());

        //Perform next tick, required to resolve the promise when we are in async mode
        $queue = \GuzzleHttp\Promise\queue();
        $queue->run();

        $rejectionReason = null;

        $queryDeferred->promise()->otherwise(function ($reason) use (&$rejectionReason) {
            $rejectionReason = $reason;
        });

        $this->assertInstanceOf(RuntimeException::class, $rejectionReason);
    }
}
