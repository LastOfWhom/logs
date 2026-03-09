<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\LogPublisherInterface;
use App\DTO\LogEntryDTO;
use App\Exception\PublishException;
use App\Message\LogMessage;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Exception\ExceptionInterface as MessengerException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Публикует записи логов в RabbitMQ через Symfony Messenger
 */
final class LogPublisherService implements LogPublisherInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {}

    public function publishBatch(array $logs, string $batchId): void
    {
        foreach ($logs as $log) {
            $this->publishOne($log, $batchId);
        }
    }

    private function publishOne(LogEntryDTO $log, string $batchId): void
    {
        $message = new LogMessage(
            log:      $log->toArray(),
            batchId:  $batchId,
            priority: $log->level->getPriority(),
        );

        try {
            $this->messageBus->dispatch($message, [
                new AmqpStamp(
                    routingKey: 'ingest',
                    flags:      AMQP_NOPARAM,
                    attributes: ['priority' => $log->level->getPriority()],
                ),
            ]);
        } catch (MessengerException $e) {
            throw new PublishException(
                sprintf('Failed to dispatch log message: %s', $e->getMessage()),
                previous: $e,
            );
        }
    }
}
