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

use Flownative\Nats\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    /**
     * @test
     */
    public function responseContainsPayload(): void
    {
        $response = new Response('+OK');
        self::assertSame('+OK', $response->getPayload());
    }

    /**
     * @test
     */
    public function responseDetectsErrors(): void
    {
        $response = new Response('+OK');
        self::assertFalse($response->isError());

        $response = new Response("-ERR 'Authorization Violation'");
        self::assertSame("-ERR 'Authorization Violation'", $response->getPayload());
        self::assertTrue($response->isError());

        $response = new Response('');
        self::assertTrue($response->isError());
    }
}
