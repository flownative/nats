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
use PackageVersions\Versions;

class Connection
{
    /**
     * @var Uri
     */
    private $connectionUri;

    /**
     * @var ConnectionOptions
     */
    private $connectionOptions;

    /**
     * @var StreamSocketConnection
     */
    private $streamSocketConnection;

    /**
     * @var ServerInfo
     */
    private $serverInfo;

    /**
     * @var array
     */
    private $subscriptions = [];

    /**
     * @var int
     */
    private $pings = 0;

    /**
     * @var int
     */
    private $pubs = 0;

    /**
     * @param Uri|string $connectionUri
     * @param ConnectionOptions|array|null $connectionOptions
     * @param StreamSocketConnection|null $streamSocketConnection
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    public function __construct($connectionUri, $connectionOptions = null, StreamSocketConnection $streamSocketConnection = null)
    {
        $this->connectionUri = $this->initializeConnectionUri($connectionUri);
        $this->connectionOptions = $this->initializeConnectionOptions($connectionOptions);
        $this->streamSocketConnection = $streamSocketConnection ?? new StreamSocketConnection($this->connectionUri, $this->connectionOptions);

        printf($this->connectionOptions->isDebug() ? " 🚀  Connecting with server via %s ...\n" : '', $connectionUri);
        $this->connect();
        $this->ping();
    }

    /**
     * @throws ConnectionException
     */
    private function connect(): void
    {
        try {
            $this->streamSocketConnection->send('CONNECT ' . $this->connectionOptions->asJson());
        } catch (ConnectionException $sendConnectionException) {
            // Error handling for unexpected errors, such as network failures or dropped connections:
            try {
                $exception = $sendConnectionException;
                $response = $this->streamSocketConnection->receive();
                if ($response->isError()) {
                    $exception = new ConnectionException(sprintf('nats: CONNECT failed with message %s', $response->getErrorMessage()), 1553677975);
                }
            } catch (ConnectionException $receiveConnectionException) {
            }
            throw $exception;
        }

        $connectResponse = $this->streamSocketConnection->receive();
        if ($connectResponse->isError()) {
            throw new ConnectionException(sprintf('nats: CONNECT failed with message %s', $connectResponse->getErrorMessage()), 1553708645);
        }
        $this->serverInfo = ServerInfo::fromResponse($connectResponse);
    }

    /**
     * Sends PING message
     *
     * @return void
     * @throws ConnectionException
     */
    public function ping(): void
    {
        $this->streamSocketConnection->send('PING');
        $pingResponse = $this->streamSocketConnection->receive();
        if ($pingResponse->isError()) {
            throw new ConnectionException(sprintf('nats: PING failed with message %s', $pingResponse->getErrorMessage()), 1553681058);
        }
        ++$this->pings;
    }

    /**
     * @param string $subject
     * @param string|null $payload
     * @param string|null $inbox
     * @throws ConnectionException
     */
    public function publish(string $subject, string $payload = null, string $inbox = null): void
    {
        $message = 'PUB ' . $subject . ($inbox !== null ? ' ' . $inbox : '');
        $message .= ' ' . strlen($payload);
        $this->streamSocketConnection->send($message . "\r\n" . $payload);
        ++$this->pubs;

    }

    /**
     * @param string $subject Message topic
     * @param string $payload Message data
     * @param \Closure $callback Closure to be executed as callback.
     * @return void
     * @throws ConnectionException
     */
    public function request(string $subject, string $payload, \Closure $callback): void
    {
        $inbox = uniqid('_INBOX.', true);
        $sid = $this->subscribe(
            $inbox,
            $callback
        );
        $this->unsubscribe($sid, 1);
        $this->publish($subject, $payload, $inbox);
        $this->wait(1);
    }

    /**
     * @param string $subject
     * @param \Closure $callback
     * @return string The subscription identifier
     * @throws ConnectionException
     */
    public function subscribe(string $subject, \Closure $callback): string
    {
        $subscriptionId = $this->generateRandomString(20);
        $message = 'SUB ' . $subject . ' ' . $subscriptionId;
        $this->streamSocketConnection->send($message);
        $this->subscriptions[$subscriptionId] = $callback;
        return $subscriptionId;
    }

    /**
     * @param string $sid
     * @param int $numberOfMessages
     * @return void
     * @throws ConnectionException
     */
    public function unsubscribe(string $sid, int $numberOfMessages = null): void
    {
        $message = 'UNSUB ' . $sid . ($numberOfMessages ? ' ' . $numberOfMessages : '');
        $this->streamSocketConnection->send($message);
        if ($numberOfMessages === null) {
            unset($this->subscriptions[$sid]);
        }
    }

    /**
     * @param int $numberOfMessages
     * @throws ConnectionException
     */
    public function wait(int $numberOfMessages): void
    {
        $this->streamSocketConnection->wait(
            $numberOfMessages,
            function (string $line) {
                $parts = explode(' ', $line);
                $subject = null;
                $payloadLength = trim($parts[3]);
                $sid = $parts[2];

                if (count($parts) === 5) {
                    $payloadLength = trim($parts[4]);
                    $subject = $parts[3];
                } else if (count($parts) === 4) {
                    $payloadLength = trim($parts[3]);
                    $subject = $parts[1];
                }

                $payload = $this->streamSocketConnection->receive($payloadLength);
                $message = new Message($subject, $payload, $sid, $this);

                if (!isset($this->subscriptions[$sid])) {
                    throw new ConnectionException(sprintf('nats: no subscription found with identifier "%s"', $sid), 1553847197);
                }

                $this->subscriptions[$sid]($message);
            }
        );
    }

    /**
     * @return string
     */
    public function getClientVersion(): string
    {
        return Versions::getVersion('flownative/nats');
    }

    /**
     * @param $connectionUri
     * @return Uri
     */
    private function initializeConnectionUri($connectionUri): Uri
    {
        if ($connectionUri instanceof Uri) {
            return $connectionUri;
        }
        if (is_string($connectionUri)) {
            return new Uri($connectionUri);
        }
        throw new \InvalidArgumentException(sprintf('nats: connectionUri must be of type string or %s, %s given', Uri::class, gettype($connectionUri)));
    }

    /**
     * @param ConnectionOptions|array|null $connectionOptions
     * @return ConnectionOptions
     * @throws ConfigurationException
     */
    private function initializeConnectionOptions($connectionOptions): ConnectionOptions
    {
        if (!$connectionOptions instanceof ConnectionOptions) {
            if ($connectionOptions === null) {
                $connectionOptions = new ConnectionOptions([]);
            } elseif (is_array($connectionOptions)) {
                $connectionOptions = new ConnectionOptions($connectionOptions);
            } else {
                throw new \InvalidArgumentException(sprintf('nats: connectionOptions must be of type array or %s, %s given', ConnectionOptions::class, gettype($connectionOptions)));
            }
        }
        if ($connectionOptions->getVersion() === null) {
            $connectionOptions->setVersion($this->getClientVersion());
        }
        return $connectionOptions;
    }

    /**
     * @param int $length
     * @return string Random string.
     */
    private function generateRandomString(int $length): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($alphabet) - 1;
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $alphabet[mt_rand(0, $max)];
        }
        return $string;
    }

}

