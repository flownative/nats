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

final class Message
{
    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    public $body;

    /**
     * Message subscriber id.
     *
     * @var string
     */
    private $sid;

    /**
     * Message related connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * @param string $subject
     * @param string $body
     * @param string $sid
     * @param Connection $connection
     */
    public function __construct(string $subject, string $body, string $sid, Connection $connection)
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->sid = $sid;
        $this->connection = $connection;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getSid(): string
    {
        return $this->sid;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Reply to this message, using the same subject
     *
     * @param string $replyBody
     * @throws ConnectionException
     */
    public function reply(string $replyBody): void
    {
        $this->connection->publish(
            $this->subject,
            $replyBody
        );
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->body;
    }
}
