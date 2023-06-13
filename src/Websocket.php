<?php

declare(strict_types=1);

namespace Exan\RevoltPhp;

use Evenement\EventEmitter;
use Exception;
use JsonSerializable;
use Psr\Log\LoggerInterface;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket as RatchetWebsocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\Promise;
use React\Socket\Connector as SocketConnector;

class Websocket extends EventEmitter
{
    private Connector $connector;

    private RatchetWebsocket $connection;

    private LoopInterface $loop;
    private SocketConnector $socketConnector;

    public function __construct(
        private readonly int $timeout,
        private readonly LoggerInterface $logger
    ) {
        $this->loop = Loop::get();
        $this->socketConnector = new SocketConnector(['timeout' => $this->timeout]);

        $this->connector = new Connector(
            $this->loop,
            $this->socketConnector
        );
    }

    /**
     * @throws ConnectionNotInitializedException
     */
    private function mustHaveActiveConnection(): void
    {
        if (!isset($this->connection)) {
            throw new Exception();
        }
    }

    public function open(string $url): ExtendedPromiseInterface
    {
        $this->logger->debug('Client: Attempting connection', ['url' => $url]);

        return new Promise(function (callable $resolver, callable $reject) use ($url) {
            ($this->connector)($url)->then(function (RatchetWebsocket $connection) use ($url, $resolver) {
                $this->connection = $connection;

                $this->logger->info('Client: Connection esablished', ['url' => $url]);

                $connection->on('message', function (MessageInterface $message) {
                    $this->logger->debug('Server: New message', ['message' => $message]);
                    $this->emit('message', [$message]);
                });

                $resolver();
            }, function (\Exception $e) use ($url, $reject) {
                $this->logger->error(
                    'Client: Error connecting to server',
                    ['url' => $url, 'error' => $e->getMessage()]
                );

                $reject(new Exception(previous: $e));
            });
        });
    }

    /**
     * @throws ConnectionNotInitializedException
     */
    public function close(int $code, string $reason): void
    {
        $this->mustHaveActiveConnection();

        $this->logger->info(
            'Client: Closing connection',
            ['code' => $code, 'reason' => $reason]
        );

        $this->connection->close($code, $reason);

        unset($this->connection);
    }

    /**
     * @throws ConnectionNotInitializedException
     */
    public function send(string $message): void
    {
        $this->mustHaveActiveConnection();

        $action = function () use ($message) {
            $this->connection->send($message);
            $this->logger->debug('Client: New message', [$message]);
        };

        $action();
    }

    /**
     * @throws ConnectionNotInitializedException
     */
    public function sendAsJson(array|JsonSerializable $item): void
    {
        $this->send(json_encode($item));
    }
}
