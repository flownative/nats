[![MIT license](http://img.shields.io/badge/license-MIT-brightgreen.svg)](http://opensource.org/licenses/MIT)
[![Packagist](https://img.shields.io/packagist/v/flownative/nats.svg)](https://packagist.org/packages/flownative/nats)

# NATS PHP Client

A PHP implementation of a client for [NATS](https://nats.io/).

🚧 Please note that this package is currently under development and not ready for general use yet. 

## Usage

Install the package using composer:

```
composer require flownative/nats
```

In PHP, open a new connection with

```php
use Flownative\Nats\Connection;

// Connect to a server
$nats = new Connection(
    'nats://localhost:4222',
    [
        'username' => 'nats',
        'password' => 'password',
        'debug' => true
    ]
);

// Simple publisher
$nats->publish('foo', 'Hello World');

// Simple asynchronous subscriber
$nats->subscribe('foo', function($message) {
    printf("\nReceived a message: %s\n", $message->getBody());
});

```

This will open a new socket connection and send a CONNECT and PING to the given NATS server an do a simple PUB / SUB run:

```
 🚀  Connecting with server via nats://localhost:4222 ...
>>>> CONNECT {"lang":"php","version":"dev-master@7dd6908c3e9f26e1094873a510547cd950bcb2c7","verbose":false,"pedantic":false,"user":"nats","pass":"password"}
<<<< INFO {"server_id":"MKfYbh2u0ZDgZrI5B1UaAv","version":"1.4.1","proto":1,"git_commit":"3e64f0b","go":"go1.11.5","host":"0.0.0.0","port":4222,"auth_required":true,"max_payload":1048576,"client_id":69} 
>>>> PING
<<<< PONG
>>>> SUB foo vjxX30gfutwGDBxfLuKR
>>>> PUB foo 11
Hello World
<<<< MSG foo vjxX30gfutwGDBxfLuKR 11
<<<< Hello World
Received a message via sid vjxX30gfutwGDBxfLuKR: Hello World

```

You can reply to a message in the subscription handler like so:

```php
// New subscribe which replies to a given message:
$nats->subscribe('hello', function (Message $message) {
    $message->reply(sprintf('Hello, %s!', $message->getBody()));
});

// Send a request which will be answered by the "hello" subscriber:
$nats->request(
    'hello',
    'Robert',
    function (Message $message) {
        printf("Request returned: %s\n", $message->getBody());
    }
);
```
## Development

### Tests

Unit tests can be executed with PhpUnit. A configuration file is included in the main directory. Unit tests are
self-contained and don't need an actual NATS server running.

### Protocol Buffer schema for NATS Streaming
Since NATS Streaming uses Google's Protocol Buffers for its messages, we need corresponding PHP classes according
to the given schema. This is defined by means of a .proto file, which retrieved from the [Go Nats Streaming project
on Github](https://github.com/nats-io/go-nats-streaming/blob/master/pb/protocol.proto). Based on this file, you can
automatically create corresponding PHP code using the Protocol Buffers Compiler.

[https://developers.google.com/protocol-buffers/docs/downloads](Download) and install the compiler (called "protoc")

## Credits
This package was developed by Robert Lemke as part of his work at [Flownative](https://www.flownative.com). It was written from
scratch, but significantly inspired by [the work by Raül Pérez](https://github.com/repejota/phpnats).

## License

This package is licensed under the MIT license.

## Contributions

Pull-Requests are welcome. Make sure to read the [Code Of Conduct](CodeOfConduct.rst).
