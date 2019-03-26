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

final class Connection
{
    /**
     * @var string
     */
    private $connectionUri;

    /**
     * @var ConnectionOptions
     */
    private $connectionOptions;

    /**
     * The timeout in microseconds
     *
     * @var int
     */
    private $timeout;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var resource
     */
    private $streamSocket;

    /**
     * @var ServerInfo
     */
    private $serverInfo;

    /**
     * @param string $connectionUri
     * @param ConnectionOptions|array|null $connectionOptions
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    public function __construct(string $connectionUri, $connectionOptions = null)
    {
        if (!$connectionOptions instanceof ConnectionOptions) {
            if ($connectionOptions === null) {
                $connectionOptions = new ConnectionOptions([]);
            } elseif(is_array($connectionOptions)) {
                $connectionOptions = new ConnectionOptions($connectionOptions);
            } else {
                throw new \InvalidArgumentException(sprintf('Connection options must be of type array or %s, %s given.', ConnectionOptions::class, gettype($connectionOptions)));
            }
        }
        $this->connectionUri = $connectionUri;
        $this->connectionOptions = $connectionOptions;
        $this->connect();
    }

    /**
     * @throws ConnectionException
     */
    private function connect(): void
    {
        $this->streamSocket = $this->openStream($this->connectionUri, $this->connectionOptions->getTimeout());

        $message = 'CONNECT ' . $this->connectionOptions->asJson();
        $this->send($message);
        $response = $this->receive();
        if ($response->isError()) {
            throw new ConnectionException(sprintf('Failed connecting to NATS server at %s: %s', $this->connectionUri, $response->getPayload()), 1553582009);
        }

        $this->serverInfo = ServerInfo::fromResponse($response);

#        \Neos\Flow\var_dump($this->serverInfo);
//        $this->ping();
//        $pingResponse = $this->receive();

//        if ($this->isErrorResponse($pingResponse) === true) {
//            throw Exception::forFailedPing($pingResponse);
//        }
    }

    /**
     * @param string $payload
     * @return void
     * @throws ConnectionException
     */
    private function send(string $payload): void
    {
        $message = $payload . "\r\n";
        $length = strlen($message);
        while (true) {
            try {
                $bytesSent = fwrite($this->streamSocket, $message);
            } catch (\Throwable $throwable) {
                throw new ConnectionException(sprintf('Failed sending data to NATS server at %s:  %s', $this->connectionUri, $throwable->getMessage()), 1553534850);
            }
            if ($bytesSent === false) {
                throw new ConnectionException(sprintf('Failed sending data to NATS server at %s.', $this->connectionUri), 1553533564);
            }
            if ($bytesSent === 0) {
                throw new ConnectionException(sprintf('Failed sending data to NATS server at %s: broken pipe or lost connection.', $this->connectionUri), 1553533614);
            }
            if ($length -= $bytesSent > 0) {
                $message = substr($message, 0 - $length);
            } else {
                break;
            }
        }

        if ($this->connectionOptions->isDebug()) {
            printf('>>>> %s', $message);
        }
    }

    /**
     * @param integer $length Number of bytes to receive
     * @return Response
     */
    private function receive($length = 0): Response
    {
        if ($length > 0) {
            $bytesToRead = $this->connectionOptions->getChunkSize();
            $line = null;
            $receivedBytes = 0;
            while ($receivedBytes < $length) {
                $bytesLeft = ($length - $receivedBytes);
                if ($bytesLeft < $this->connectionOptions->getChunkSize()) {
                    $bytesToRead = $bytesLeft;
                }

                $readChunk = fread($this->streamSocket, $bytesToRead);
                $receivedBytes += strlen($readChunk);
                $line .= $readChunk;
            }
        } else {
            $line = fgets($this->streamSocket);
        }

        if ($this->debug) {
            printf('<<<< %s\r\n', $line);
        }

        return new Response($line);
    }

    /**
     * @param string $uri
     * @param int $timeout
     * @return resource
     * @throws ConnectionException
     */
    private function openStream(string $uri, int $timeout)
    {
        $stream = stream_socket_client($uri, $errorNumber, $errorMessage, $timeout / 1000, STREAM_CLIENT_CONNECT);
        if ($stream === false) {
            throw new ConnectionException(sprintf('Failed connecting to NATS server at %s: %s (%s)', $uri, $errorMessage, $errorNumber), 1553532561);
        }

        $seconds = floor($timeout / 1000);
        $microseconds = ($timeout - $seconds) * 1000;
        stream_set_timeout($stream, $seconds, $microseconds);

        return $stream;
    }
}

