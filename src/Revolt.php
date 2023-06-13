<?php

namespace Exan\RevoltPhp;

use Exan\RevoltPhp\Bonfire;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

class Revolt
{
    public const VERSION = '1';

    public readonly Bonfire $bonfire;

    public function __construct(
        private readonly LoopInterface  $loop,
        private readonly string $token,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function withBonfire(int $heartbeatInterval = 20, string $websocketUrl = 'wss://ws.revolt.chat?version=1&format=json'): static
    {
        $this->bonfire = new Bonfire(
            $this->token,
            $websocketUrl,
            $heartbeatInterval,
            $this->loop,
            $this->logger,
        );

        return $this;
    }
}
