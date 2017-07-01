<?php

declare(strict_types=1);

namespace ProophTest\ServiceBus\Message\Http\Container;

use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use Http\Message\UriFactory;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Prooph\ServiceBus\Message\Http\Container\HttpMessageProducerFactory;
use Prooph\ServiceBus\Message\Http\HttpMessageProducer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriInterface;

class HttpMessageProducerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_producer_using_call_static(): void
    {
        $uri = $this->prophesize(UriInterface::class);

        $client = $this->prophesize(HttpClient::class);

        $uriFactory = $this->prophesize(UriFactory::class);
        $uriFactory->createUri('http://localhost:8080')->willReturn($uri->reveal())->shouldBeCalled();

        $requestFactory = $this->prophesize(RequestFactory::class);

        $config = [
            'prooph' => [
                'http-producer' => [
                    'default' => [
                        'uri_factory' => UriFactory::class,
                        'uri' => 'http://localhost:8080',
                        'request_factory' => RequestFactory::class,
                    ],
                ],
            ],
        ];

        $container = $this->prophesize(ContainerInterface::class);

        $container->get(HttpClient::class)->willReturn($client->reveal())->shouldBeCalled();
        $container->get(NoOpMessageConverter::class)->willReturn(new NoOpMessageConverter())->shouldBeCalled();
        $container->get('config')->willReturn($config)->shouldBeCalled();
        $container->get(UriFactory::class)->willReturn($uriFactory->reveal())->shouldBeCalled();
        $container->get(RequestFactory::class)->willReturn($requestFactory->reveal())->shouldBeCalled();

        $name = 'default';
        $producer = HttpMessageProducerFactory::$name($container->reveal());

        $this->assertInstanceOf(HttpMessageProducer::class, $producer);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_container_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $name = 'default';
        HttpMessageProducerFactory::$name('invalid');
    }
}
