<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\LogPublisherInterface;
use App\DTO\LogEntryDTO;
use App\Exception\PublishException;
use App\Message\LogMessage;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class LogPublisherService implements LogPublisherInterface
{
    private const EXCHANGE    = 'logs';
    private const QUEUE       = 'logs.ingest';
    private const ROUTING_KEY = 'ingest';

    public function __construct(
        private readonly AMQPLazyConnection $connection,
    ) {}

    public function publishBatch(array $logs, string $batchId): void
    {
        try {
            $channel = $this->connection->channel();

            $channel->exchange_declare(self::EXCHANGE, 'direct', false, true, false);
            $channel->queue_declare(
                self::QUEUE, false, true, false, false, false,
                new AMQPTable(['x-max-priority' => 3]),
            );
            $channel->queue_bind(self::QUEUE, self::EXCHANGE, self::ROUTING_KEY);

            foreach ($logs as $log) {
                $channel->basic_publish(
                    $this->buildMessage($log, $batchId),
                    self::EXCHANGE,
                    self::ROUTING_KEY,
                );
            }

            $channel->close();
        } catch (\Exception $e) {
            throw new PublishException(
                sprintf('Failed to publish logs: %s', $e->getMessage()),
                previous: $e,
            );
        }
    }

    private function buildMessage(LogEntryDTO $log, string $batchId): AMQPMessage
    {
        $message = new LogMessage(
            log:      $log->toArray(),
            batchId:  $batchId,
            priority: $log->level->getPriority(),
        );

        return new AMQPMessage(
            json_encode($message->getLog() + $message->toMetadataArray(), JSON_THROW_ON_ERROR),
            [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'priority'      => $message->getPriority(),
                'content_type'  => 'application/json',
            ],
        );
    }
}
