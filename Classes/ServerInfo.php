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

final class ServerInfo {

    /**
     * @var string
     */
    private $serverId;

    /**
     * @var string
     */
    private $host;

    /**
     * @var integer
     */
    private $port;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $goVersion;

    /**
     * @var bool
     */
    private $authRequired = false;

    /**
     * @var bool
     */
    private $tlsRequired = false;

    /**
     * @var bool
     */
    private $tlsVerify = false;

    /**
     * @var int
     */
    private $maxPayload;

    /**
     * @var int
     */
    private $clientId;

    /**
     * @var array
     */
    private $connectUrls;

    /**
     * @param Response $response
     * @return ServerInfo
     */
    public static function fromResponse(Response $response): ServerInfo
    {
        $serverInfo = new static();
        [, $jsonData] = explode(' ', $response);
        $data  = json_decode($jsonData, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('nats: server response did not contain valid JSON array with server information', 1553673904);
        }

        foreach ($data as $key => $value) {
            switch ($key) {
                case 'server_id':
                    $serverInfo->serverId = $value;
                break;
                case 'version':
                    $serverInfo->version = $value;
                break;
                case 'host':
                    $serverInfo->host = $value;
                break;
                case 'port':
                    $serverInfo->port = $value;
                break;
                case 'auth_required':
                    $serverInfo->authRequired = $value;
                break;
                case 'tls_required':
                    $serverInfo->tlsRequired = $value;
                break;
                case 'tls_verify':
                    $serverInfo->tlsVerify = $value;
                break;
                case 'go_version':
                    $serverInfo->goVersion = $value;
                break;
                case 'go':
                    $serverInfo->goVersion = $value;
                break;
                case 'max_payload':
                    $serverInfo->maxPayload = $value;
                break;
                case 'connect_urls':
                    $serverInfo->connectUrls = $value;
                break;
                case 'client_id':
                    $serverInfo->clientId = $value;
                break;
            }
        }
        return $serverInfo;
    }

    /**
     * @return string
     */
    public function getServerId(): string
    {
        return $this->serverId;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getGoVersion(): string
    {
        return $this->goVersion;
    }

    /**
     * @return bool
     */
    public function isAuthRequired(): bool
    {
        return $this->authRequired;
    }

    /**
     * @return bool
     */
    public function isTlsRequired(): bool
    {
        return $this->tlsRequired;
    }

    /**
     * @return bool
     */
    public function isTlsVerify(): bool
    {
        return $this->tlsVerify;
    }

    /**
     * @return int
     */
    public function getMaxPayload(): int
    {
        return $this->maxPayload;
    }

    /**
     * @return int
     */
    public function getClientId(): int
    {
        return $this->clientId;
    }

    /**
     * @return array
     */
    public function getConnectUrls(): array
    {
        return $this->connectUrls;
    }
}

