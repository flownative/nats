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

final class ConnectionOptions
{
    /**
     * Version of this client
     *
     * @var string
     */
    private $version;

    /**
     * Username for authentication by user
     *
     * @var string|null
     */
    private $username = null;

    /**
     * Password for authentication by user
     *
     * @var string|null
     */
    private $password = null;

    /**
     * Token for authentication by token
     *
     * @var string|null
     */
    private $token = null;

    /**
     * @var bool
     */
    private $pedantic = false;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * Timeout in seconds
     *
     * @var int
     */
    private $timeout;

    /**
     * @var bool
     */
    private $reconnect = true;

    /**
     * @var int
     */
    private $chunkSize = 1500;

    /**
     * @param array $options
     * @throws ConfigurationException
     */
    public function __construct(array $options)
    {
        foreach ($options as $name => $value) {
            $setterName = 'set' . ucfirst($name);
            if (method_exists($this, $setterName)) {
                $this->$setterName($value);
            } else {
                throw new ConfigurationException(sprintf('NATS client: invalid configuration option "%s".', $name), 1553531607);
            }
        }

        if ($this->timeout === null) {
            $this->timeout = (int)ini_get('default_socket_timeout');
        }
    }

    /**
     * @return string
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string|null $username
     */
    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     */
    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    /**
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param string|null $token
     */
    public function setToken(?string $token): void
    {
        $this->token = $token;
    }

    /**
     * @return bool
     */
    public function isPedantic(): bool
    {
        return $this->pedantic;
    }

    /**
     * @param bool $pedantic
     */
    public function setPedantic(bool $pedantic): void
    {
        $this->pedantic = $pedantic;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * @return bool
     */
    public function isReconnect(): bool
    {
        return $this->reconnect;
    }

    /**
     * @param bool $reconnect
     */
    public function setReconnect(bool $reconnect): void
    {
        $this->reconnect = $reconnect;
    }

    /**
     * @return int
     */
    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    /**
     * @param int $chunkSize
     */
    public function setChunkSize(int $chunkSize): void
    {
        $this->chunkSize = $chunkSize;
    }

    /**
     * @return string
     */
    public function asJson(): string
    {
        $array = [
            'lang' => 'php',
            'version' => $this->version,
            'verbose' => false,
            'pedantic' => $this->pedantic,
        ];

        if ($this->username !== null) {
            $array['user'] = $this->username;
        }

        if ($this->password !== null) {
            $array['pass'] = $this->password;
        }

        if ($this->token !== null) {
            $array['auth_token'] = $this->token;
        }

        return json_encode($array);
    }

}

