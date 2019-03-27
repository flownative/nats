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

use Flownative\Nats\ConfigurationException;
use Flownative\Nats\Connection;
use Flownative\Nats\ConnectionException;
use Flownative\Nats\ConnectionOptions;
use Flownative\Nats\Response;
use Flownative\Nats\StreamSocketConnection;
use PHPUnit\Framework\TestCase;

class ConnectionTest extends TestCase
{
    /**
     * @var ConnectionOptions
     */
    private $defaultOptions;

    /**
     * @throws
     */
    public function setUp()
    {
        parent::setUp();
        $this->defaultOptions = new ConnectionOptions([
            'username' => 'user',
            'password' => 'password',
            'debug' => false,
            'version' => '1.0.0'
        ]);
    }

    /**
     * @test
     * @throws
     */
    public function constructorConnectsWithServer(): void
    {
        $mockSocket = $this->mockSocket();
        $mockSocket->expects($this->exactly(2))->method('receive')
            ->willReturnOnConsecutiveCalls(
                new Response('INFO {"server_id":"7rCY5EiVoJnVPso7zhEfkI","version":"1.4.1","proto":1,"git_commit":"3e64f0b","go":"go1.11.5","host":"0.0.0.0","port":4222,"max_payload":1048576,"client_id":35}'),
                new Response('PONG')
            );
        $mockSocket->expects($this->exactly(2))->method('send')
            ->withConsecutive(
                ['CONNECT {"lang":"php","version":"1.0.0","verbose":false,"pedantic":false,"user":"user","pass":"password"}'],
                ['PING']
            );

        new Connection('nats://localhost:4222', $this->defaultOptions, $mockSocket);
    }

    /**
     * @test
     * @throws
     */
    public function connectAuthenticatesWithUsernameAndPasswordIfRequired(): void
    {
        $mockSocket = $this->mockSocket();
        $mockSocket->expects($this->exactly(2))->method('receive')
            ->willReturnOnConsecutiveCalls(
                new Response('INFO {"server_id":"7rCY5EiVoJnVPso7zhEfkI","version":"1.4.1","proto":1,"git_commit":"3e64f0b","go":"go1.11.5","host":"0.0.0.0","port":4222,"auth_required":true,"max_payload":1048576,"client_id":35}'),
                new Response('PONG')
            );

        $mockSocket->expects($this->exactly(2))->method('send')
            ->withConsecutive(
                ['CONNECT {"lang":"php","version":"1.0.0","verbose":false,"pedantic":false,"user":"user","pass":"password"}'],
                ['PING']
            );
        new Connection('nats://localhost:4222', $this->defaultOptions, $mockSocket);
    }

    /**
     * @test
     * @expectedException \Flownative\Nats\ConnectionException
     * @expectedExceptionCode 1553677975
     * @throws ConfigurationException
     */
    public function connectThrowsExceptionOnWrongCredentials(): void
    {
        $options = new ConnectionOptions([
            'username' => 'user',
            'password' => 'wrong-password',
            'debug' => false,
            'version' => '1.0.0'
        ]);

        $mockSocket = $this->mockSocket();
        $mockSocket->expects($this->once())->method('receive')
            ->willReturnOnConsecutiveCalls(
                new Response("-ERR 'Authorization Violation'")
            );
        $mockSocket->expects($this->once())->method('send')
            ->with('CONNECT {"lang":"php","version":"1.0.0","verbose":false,"pedantic":false,"user":"user","pass":"wrong-password"}')
            ->willThrowException(new ConnectionException(sprintf('nats: sending data failed: %s', 'fwrite() ...'), 1553534850));

        new Connection('nats://localhost:4222', $options, $mockSocket);
    }

    /**
     * @test
     * @throws
     */
    public function publish(): void
    {
        $mockSocket = $this->mockSocket();
        $mockSocket->expects($this->exactly(2))->method('receive')
            ->willReturnOnConsecutiveCalls(
                new Response('INFO {"server_id":"7rCY5EiVoJnVPso7zhEfkI","version":"1.4.1","proto":1,"git_commit":"3e64f0b","go":"go1.11.5","host":"0.0.0.0","port":4222,"max_payload":1048576,"client_id":35}'),
                new Response('PONG')
            );
        $mockSocket->expects($this->exactly(3))->method('send')
            ->withConsecutive(
                ['CONNECT {"lang":"php","version":"1.0.0","verbose":false,"pedantic":false,"user":"user","pass":"password"}'],
                ['PING'],
                ["PUB FOO 27 11\r\nHello World"]
            );

        $connection = new Connection('nats://localhost:4222', $this->defaultOptions, $mockSocket);
        $connection->publish('FOO', 'Hello World', 27);

    }

    /**
     * @test
     * @throws
     */
    public function subscribe(): void
    {
        $mockSocket = $this->mockSocket();
        $mockSocket->expects($this->exactly(2))->method('receive')
            ->willReturnOnConsecutiveCalls(
                new Response('INFO {"server_id":"7rCY5EiVoJnVPso7zhEfkI","version":"1.4.1","proto":1,"git_commit":"3e64f0b","go":"go1.11.5","host":"0.0.0.0","port":4222,"max_payload":1048576,"client_id":35}'),
                new Response('PONG')
            );
        $mockSocket->expects($this->exactly(3))->method('send')
            ->withConsecutive(
                ['CONNECT {"lang":"php","version":"1.0.0","verbose":false,"pedantic":false,"user":"user","pass":"password"}'],
                ['PING'],
                ['SUB foo PzioLnvdR4cXQfrIitw6']
            );

        mt_srand(1);
        $connection = new Connection('nats://localhost:4222', $this->defaultOptions, $mockSocket);
        $connection->subscribe('foo', function($message) {
            return $message->getData();
        });
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|StreamSocketConnection
     */
    private function mockSocket(): \PHPUnit_Framework_MockObject_MockObject
    {
        return $this->createMock(StreamSocketConnection::class);
    }
}
