<?php
/*
 * This file is part of the codeliner/psb-http-dispatcher.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.10.14 - 22:34
 */
namespace Prooph\ServiceBusTest;

use GuzzleHttp\Client;
use GuzzleHttp\Ring\Client\MockHandler;
use GuzzleHttp\Subscriber\History;
use Prooph\ServiceBus\Message\FromMessageTranslator;
use Prooph\ServiceBus\Message\Http\MessageDispatcher;
use Prooph\ServiceBus\Message\StandardMessage;
use Prooph\ServiceBus\Message\ToMessageTranslator;
use Prooph\ServiceBusTest\Mock\DoSomething;

/**
 * Class HttpMessageDispatcherTest
 *
 * @package Prooph\ServiceBusTest
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class HttpMessageDispatcherTest extends TestCase
{
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var History
     */
    private $requestHistory;

    /**
     * @var ToMessageTranslator
     */
    private $toMessageTranslator;

    /**
     * @var FromMessageTranslator
     */
    private $fromMessageTranslator;

    protected function setUp()
    {
        $mockedHandler = new MockHandler(['status' => 201]);

        $this->guzzleClient = new Client(['handler' => $mockedHandler]);

        $this->requestHistory = new History();

        $this->guzzleClient->getEmitter()->attach($this->requestHistory);

        $this->toMessageTranslator = new ToMessageTranslator();

        $this->fromMessageTranslator = new FromMessageTranslator();
    }

    /**
     * @test
     */
    public function it_sends_message_as_a_http_post_request_to_specified_uri()
    {
        $doSomething = DoSomething::fromData("test command");

        $message = $this->toMessageTranslator->translateToMessage($doSomething);

        $messageDispatcher = new MessageDispatcher($this->guzzleClient, '/test-api/messages');

        $messageDispatcher->dispatch($message);

        $this->assertEquals(1, $this->requestHistory->count());

        $request = $this->requestHistory->getLastRequest();

        $messageArr = json_decode($request->getBody(), true);

        $doSomethingSend = $this->fromMessageTranslator->translateFromMessage(StandardMessage::fromArray($messageArr));

        $this->assertEquals($doSomething->payload(), $doSomethingSend->payload());

        $this->assertEquals('/test-api/messages', $request->getResource());
    }

    /**
     * @test
     */
    public function it_uses_default_uri_when_no_different_one_is_specified()
    {
        $doSomething = DoSomething::fromData("test command");

        $message = $this->toMessageTranslator->translateToMessage($doSomething);

        $messageDispatcher = new MessageDispatcher($this->guzzleClient);

        $messageDispatcher->dispatch($message);

        $this->assertEquals(1, $this->requestHistory->count());

        $request = $this->requestHistory->getLastRequest();

        $messageArr = json_decode($request->getBody(), true);

        $doSomethingSend = $this->fromMessageTranslator->translateFromMessage(StandardMessage::fromArray($messageArr));

        $this->assertEquals($doSomething->payload(), $doSomethingSend->payload());

        $this->assertEquals('/api/messages', $request->getResource());
    }

    /**
     * @test
     */
    public function it_throws_exception_on_failed_request_even_when_working_with_futures()
    {
        $mockedHandler = new MockHandler(['status' => 404]);

        $this->guzzleClient = new Client(['handler' => $mockedHandler]);

        $doSomething = DoSomething::fromData("test command");

        $message = $this->toMessageTranslator->translateToMessage($doSomething);

        $messageDispatcher = new MessageDispatcher($this->guzzleClient, null, true);

        $this->setExpectedException('GuzzleHttp\Exception\ClientException');

        $messageDispatcher->dispatch($message);
    }
}
 