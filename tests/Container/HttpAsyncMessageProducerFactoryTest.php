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

namespace ProophTest\ServiceBus\Message\Http\Container;

use Http\Client\HttpAsyncClient;
use Http\Message\RequestFactory;
use Http\Message\UriFactory;
use PHPUnit\Framework\TestCase;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Prooph\ServiceBus\Message\Http\Container\HttpAsyncMessageProducerFactory;
use Prooph\ServiceBus\Message\Http\HttpAsyncMessageProducer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriInterface;

class HttpAsyncMessageProducerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_producer_using_call_static(): void
    {
        $uri = $this->prophesize(UriInterface::class);

        $client = $this->prophesize(HttpAsyncClient::class);

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

        $container->get(HttpAsyncClient::class)->willReturn($client->reveal())->shouldBeCalled();
        $container->get(NoOpMessageConverter::class)->willReturn(new NoOpMessageConverter())->shouldBeCalled();
        $container->get('config')->willReturn($config)->shouldBeCalled();
        $container->get(UriFactory::class)->willReturn($uriFactory->reveal())->shouldBeCalled();
        $container->get(RequestFactory::class)->willReturn($requestFactory->reveal())->shouldBeCalled();

        $name = 'default';
        $producer = HttpAsyncMessageProducerFactory::$name($container->reveal());

        $this->assertInstanceOf(HttpAsyncMessageProducer::class, $producer);
    }

    /**
     * @test
     */
    public function it_throws_exception_when_invalid_container_passed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $name = 'default';
        HttpAsyncMessageProducerFactory::$name('invalid');
    }
}
