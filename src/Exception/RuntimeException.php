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

namespace Prooph\ServiceBus\Message\Http\Exception;

use Prooph\ServiceBus\Exception\RuntimeException as ServiceBusRuntimeException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class RuntimeException extends ServiceBusRuntimeException
{
    /**
     * @var ResponseInterface
     */
    private $response;

    public static function fromResponse(ResponseInterface $response): RuntimeException
    {
        return new self($response->getReasonPhrase(), $response->getStatusCode(), null, $response);
    }

    public function __construct($message = '', $code = 0, Throwable $previous = null, ResponseInterface $response = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    public function response(): ResponseInterface
    {
        return $this->response;
    }
}
