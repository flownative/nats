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

use GuzzleHttp\Psr7\Uri;

class StreamSocketConnection
{
    /**
     * @var ConnectionOptions
     */
    private $connectionOptions;

    /**
     * @var resource
     */
    private $stream;

    /**
     * @param Uri $uri
     * @param ConnectionOptions $connectionOptions
     * @throws ConnectionException
     */
    public function __construct(Uri $uri, ConnectionOptions $connectionOptions)
    {
        $this->connectionOptions = $connectionOptions;
        $timeoutInSeconds = $connectionOptions->getTimeout();

        try {
            $this->stream = stream_socket_client('tcp://' . $uri->getHost() . ':' . $uri->getPort(), $errorNumber, $errorMessage, $timeoutInSeconds, STREAM_CLIENT_CONNECT);
        } catch (\Throwable $throwable) {
            $this->stream = false;
            $errorMessage = $throwable->getMessage();
            $errorNumber = 1553713598;
        }
        if ($this->stream === false) {
            throw new ConnectionException(sprintf('nats: connection failed: %s (%s)', $errorMessage, $errorNumber), 1553532561);
        }

        stream_set_timeout($this->stream, $timeoutInSeconds, 0);
    }

    /**
     * @param string $payload
     * @return void
     * @throws ConnectionException
     */
    public function send(string $payload): void
    {
        if (!is_resource($this->stream)) {
            throw new ConnectionException('nats: sending failed, because stream was not open', 1553630021);
        }

        $payload .= "\r\n";
        if ($this->connectionOptions->isDebug()) {
            printf('>>>> %s', $payload);
        }

        $length = strlen($payload);
        while (true) {
            try {
                $bytesSent = fwrite($this->stream, $payload);
            } catch (\Throwable $throwable) {
                throw new ConnectionException(sprintf('nats: sending data failed: %s', $throwable->getMessage()), 1553534850);
            }
            if ($bytesSent === false) {
                throw new ConnectionException('nats: sending data failed', 1553533564);
            }
            if ($bytesSent === 0) {
                throw new ConnectionException('nats: sending data failed due to broken pipe or lost connection', 1553533614);
            }
            $length -= $bytesSent;
            if ($length > 0) {
                $payload = substr($payload, 0 - $length);
            } else {
                break;
            }
        }
    }

    /**
     * @param integer $length Number of bytes to receive
     * @return Response
     * @throws ConnectionException
     */
    public function receive(int $length = 0): Response
    {
        if (!is_resource($this->stream)) {
            throw new ConnectionException('nats: receiving data failed because stream was not open', 1553630040);
        }

        if ($length > 0) {
            $bytesToRead = $this->connectionOptions->getChunkSize();
            $line = null;
            $receivedBytes = 0;
            while ($receivedBytes < $length) {
                $bytesLeft = ($length - $receivedBytes);
                if ($bytesLeft < $this->connectionOptions->getChunkSize()) {
                    $bytesToRead = $bytesLeft;
                }

                $readChunk = fread($this->stream, $bytesToRead);
                $receivedBytes += strlen($readChunk);
                $line .= $readChunk;
            }
        } else {
            $line = fgets($this->stream);
        }

        if ($this->connectionOptions->isDebug()) {
            printf('<<<< %s', $line);
        }
        return new Response($line);
    }
}
