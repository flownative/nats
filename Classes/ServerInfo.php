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
    private $authRequired;

    /**
     * @var bool
     */
    private $tlsRequired;

    /**
     * @var bool
     */
    private $tlsVerify;

    /**
     * @var bool
     */
    private $sslRequired;

    /**
     * @var int
     */
    private $maxPayload;

    /**
     * @var array
     */
    private $connectUrls;

    /**
     * @param Response $response
     * @return ServerInfo
     */
    public static function fromResponse(Response $response)
    {
        $serverInfo = new static();
        $parts = explode(' ', $response);
        $data  = json_decode($parts[1], true);
        return $serverInfo;
    }
}

