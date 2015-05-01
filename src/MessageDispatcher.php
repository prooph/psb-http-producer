<?php
/*
 * This file is part of the codeliner/psb-http-dispatcher.
 * (c) Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 31.10.14 - 21:57
 */
namespace Prooph\ServiceBus\Message\Http;

use GuzzleHttp\Client;
use Prooph\Common\Messaging\RemoteMessage;
use Prooph\ServiceBus\Message\RemoteMessageDispatcher;

/**
 * Class MessageDispatcher
 *
 * Uses a already configured GuzzleHttp\Client to send a post request to the specified URI (defaults to /api/messages)
 * with a json encoded message in the request body.
 *
 * @package Prooph\ServiceBus\Message\Http
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class MessageDispatcher implements RemoteMessageDispatcher
{
    /**
     * @var Client
     */
    private $guzzleClient;

    /**
     * @var string
     */
    private $uri = "/api/messages";

    /**
     * @var bool
     */
    private $sendAsync = false;

    /**
     * @param Client $guzzleClient
     * @param null|string $uri
     * @param null|bool $sendAsync
     */
    public function __construct(Client $guzzleClient, $uri = null, $sendAsync = null)
    {
        if (!is_null($uri)) $this->useUri($uri);

        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @param string $uri starting with a slash
     * @throws \InvalidArgumentException if $uri is not a string or it not starts with a slash
     */
    public function useUri($uri)
    {
        if (! is_string($uri)) throw new \InvalidArgumentException("Uri is not a string");

        if (! strpos($uri, '/') === 0) throw new \InvalidArgumentException("Wrong URI provided: " . $uri . ". Needs to start with a slash!");

        $this->uri = $uri;
    }

    /**
     * @param bool $flag
     */
    public function sendAsync($flag)
    {
        $this->sendAsync = (bool)$flag;
    }

    /**
     * @param RemoteMessage $message
     * @return void
     */
    public function dispatch(RemoteMessage $message)
    {
        $response = $this->guzzleClient->post($this->uri, ['json' => $message->toArray(), 'future' => $this->sendAsync]);

        if ($this->sendAsync) $response->then(null, function ($error) { throw $error; });
    }
}
 