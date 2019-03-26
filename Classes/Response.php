<?php
namespace Flownative\Nats;

/*
 * This file is part of the Flownative.Nats package.
 *
 * (c) Robert Lemke, Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

final class Response
{
    /**
     * @var string
     */
    private $payload;

    /**
     * @param $payload
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    /**
     * @return bool
     */
    public function isError(): bool
    {
        return strpos($this->payload, '-ERR') === 0;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->payload;
    }
}

