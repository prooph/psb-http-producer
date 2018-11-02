<?php

/**
 * This file is part of prooph/psb-http-producer.
 * (c) 2014-2018 prooph software GmbH <contact@prooph.de>
 * (c) 2015-2018 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\ServiceBus\Message\Http\Container;

use Http\Client\HttpAsyncClient;
use Http\Discovery\UriFactoryDiscovery;
use Interop\Config\ConfigurationTrait;
use Interop\Config\ProvidesDefaultOptions;
use Interop\Config\RequiresConfigId;
use Interop\Config\RequiresMandatoryOptions;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Prooph\ServiceBus\Message\Http\HttpAsyncMessageProducer;
use Psr\Container\ContainerInterface;

final class HttpAsyncMessageProducerFactory implements ProvidesDefaultOptions, RequiresConfigId, RequiresMandatoryOptions
{
    use ConfigurationTrait;

    /**
     * @var string
     */
    private $configId;

    /**
     * Creates a new instance from a specified config, specifically meant to be used as static factory.
     *
     * In case you want to use another config key than provided by the factories, you can add the following factory to
     * your config:
     *
     * <code>
     * <?php
     * return [
     *     HttpAsyncMessageProducer::class => [HttpAsyncMessageProducerFactory::class, 'service_name'],
     * ];
     * </code>
     *
     * @throws InvalidArgumentException
     */
    public static function __callStatic(string $name, array $arguments): HttpAsyncMessageProducer
    {
        if (! isset($arguments[0]) || ! $arguments[0] instanceof ContainerInterface) {
            throw new InvalidArgumentException(
                \sprintf('The first argument must be of type %s', ContainerInterface::class)
            );
        }

        return (new static($name))->__invoke($arguments[0]);
    }

    public function __construct(string $configId = 'default')
    {
        $this->configId = $configId;
    }

    public function __invoke(ContainerInterface $container): HttpAsyncMessageProducer
    {
        $options = $this->options($container->get('config'), $this->configId);

        $client = $container->get($options['client']);
        $messageConverter = $container->get($options['message_converter']);

        $requestFactory = isset($options['request_factory']) ? $container->get($options['request_factory']) : null;
        $uriFactory = isset($options['uri_factory']) ? $container->get($options['uri_factory']) : UriFactoryDiscovery::find();

        $uri = $uriFactory->createUri($options['uri']);

        return new HttpAsyncMessageProducer($client, $messageConverter, $uri, $requestFactory);
    }

    public function dimensions(): iterable
    {
        return ['prooph', 'http-producer'];
    }

    public function defaultOptions(): iterable
    {
        return [
            'client' => HttpAsyncClient::class,
            'message_converter' => NoOpMessageConverter::class,
        ];
    }

    public function mandatoryOptions(): iterable
    {
        return [
            'uri',
        ];
    }
}
