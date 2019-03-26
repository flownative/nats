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

class ConnectionOptionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array
     */
    public function validOptions(): array
    {
        return [
            ['username', null],
            ['password', null],
            ['token', null],
            ['pedantic', false],
            ['verbose', false],
            ['debug', false],
            ['timeout', 5],
            ['reconnect', true],
            ['chunkSize', 1500]
        ];
    }

    /**
     * @param $name
     * @param $value
     * @throws
     * @test
     * @dataProvider validOptions()
     */
    public function validOptionsCanBeSet($name, $value): void
    {
        $options = new ConnectionOptions([$name => $value]);
        $getterName = 'get' . ucfirst($name);
        self::assertSame($value, $options->$getterName);
    }

}
