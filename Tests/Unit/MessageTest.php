<?php
namespace Flownative\Nats\Tests\Unit;

/*
 * This file is part of the Flownative.Nats package.
 *
 * (c) Robert Lemke, Flownative GmbH - www.flownative.com
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flownative\Nats\Connection;
use Flownative\Nats\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /**
     * @test
     */
    public function messageContainsConstructorValues(): void
    {
        $connection = $this->createMock(Connection::class);
        assert($connection instanceof Connection);

        $message = new Message(
            'subject',
            'the message body',
            'SubscriberIdentifier',
            $connection
        );

        self::assertSame('subject', $message->getSubject());
        self::assertSame('the message body', $message->getBody());
        self::assertSame('SubscriberIdentifier', $message->getSid());
        self::assertSame($connection, $message->getConnection());
    }

    /**
     * @test
     */
    public function messageReplySendsAReply(): void
    {
        $connection = $this->createMock(Connection::class);
        assert($connection instanceof Connection);

        $message = new Message(
            'subject',
            'the message body',
            'SubscriberIdentifier',
            $connection
        );

        $connection->expects($this->once())->method('publish')->with('subject', 'the reply body');
        $message->reply('the reply body');
    }
}
