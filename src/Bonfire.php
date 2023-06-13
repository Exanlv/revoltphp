<?php

namespace Exan\RevoltPhp;

use Exan\Eventer\Eventer;
use Psr\Log\LoggerInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\LoopInterface;

class Bonfire
{
    public readonly Eventer $raw;

    private readonly Websocket $websocket;

    public function __construct(
        private readonly string $token,
        private readonly string $connectionUrl,
        private readonly int $heartbeatInterval,
        private readonly LoopInterface $loop,
        private readonly LoggerInterface $logger,

    ) {
        $this->websocket = new Websocket(
            20,
            $this->logger,
        );

        $this->websocket->on('message', function (MessageInterface $message) {
            $payload = json_decode((string) $message);

            var_dump($payload);
        });
    }

    public function connect()
    {
        return $this->websocket->open($this->connectionUrl)
            ->done(function () {
                $this->authenticate();
                $this->startHeartbeats();
            });
    }

    private function authenticate()
    {
        $this->websocket->sendAsJson([
            'type' => 'Authenticate',
            'token' => $this->token,
        ]);
    }

    private function startHeartbeats()
    {
        $this->loop->addPeriodicTimer($this->heartbeatInterval, $this->sendHeartbeat(...));
    }

    private function sendHeartbeat()
    {
        $this->websocket->sendAsJson([
            'type' => 'Ping',
            'data' => 0,
        ]);
    }
}
