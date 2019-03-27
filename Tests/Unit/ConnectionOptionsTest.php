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

use Flownative\Nats\ConnectionOptions;
use PHPUnit\Framework\TestCase;

class ConnectionOptionsTest extends TestCase
{
    /**
     * @return array
     */
    public function validOptions(): array
    {
        return [
            ['username', null, 'getUsername'],
            ['password', null, 'getPassword'],
            ['token', null, 'getToken'],
            ['pedantic', false, 'isPedantic'],
            ['debug', false, 'isDebug'],
            ['timeout', 5, 'getTimeout'],
            ['reconnect', true, 'isReconnect'],
            ['chunkSize', 1500, 'getChunkSize']
        ];
    }

    /**
     * @param $name
     * @param $value
     * @param $getterName
     * @throws
     * @test
     * @dataProvider validOptions()
     */
    public function validOptionsCanBeSetAndRetrieved($name, $value, $getterName): void
    {
        $options = new ConnectionOptions([$name => $value]);
        self::assertSame($value, $options->$getterName());
    }

    /**
     * @test
     * @expectedException \Flownative\Nats\ConfigurationException
     * @throws
     */
    public function invalidOptionNameIsRejected(): void
    {
        new ConnectionOptions(['foo' => 'bar']);
    }

}
