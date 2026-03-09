<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Сообщение, отправляемое в асинхронный транспорт для каждой записи лога.
 * Содержит исходные данные лога и метаданные батча
 */
final class LogMessage
{
    private readonly string $publishedAt;

    /**
     * @param array<string, mixed> $log
     * @param string               $batchId
     * @param int                  $priority
     * @param int                  $retryCount
     * @param string|null          $publishedAt
     */
    public function __construct(
        private readonly array  $log,
        private readonly string $batchId,
        private readonly int    $priority = 1,
        private readonly int    $retryCount = 0,
        ?string $publishedAt = null,
    ) {
        $this->publishedAt = $publishedAt
            ?? (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM);
    }

    /** @return array<string, mixed> */
    public function getLog(): array
    {
        return $this->log;
    }

    public function getBatchId(): string
    {
        return $this->batchId;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getPublishedAt(): string
    {
        return $this->publishedAt;
    }

    public function getRetryCount(): int
    {
        return $this->retryCount;
    }

    /**
     * Возвращает новый экземпляр с увеличенным счётчиком повторных попыток.
     * Сохраняет оригинальный publishedAt для возможности измерения задержки.
     */
    public function withIncrementedRetry(): self
    {
        return new self(
            log:         $this->log,
            batchId:     $this->batchId,
            priority:    $this->priority,
            retryCount:  $this->retryCount + 1,
            publishedAt: $this->publishedAt,
        );
    }

    /** @return array<string, mixed> */
    public function toMetadataArray(): array
    {
        return [
            'batch_id'     => $this->batchId,
            'published_at' => $this->publishedAt,
            'retry_count'  => $this->retryCount,
            'priority'     => $this->priority,
        ];
    }
}
