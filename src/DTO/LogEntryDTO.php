<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\LogLevel;

/**
 * Иммутабельный value object, представляющий одну валидированную запись лога
 */
final class LogEntryDTO
{
    public function __construct(
        public readonly string   $timestamp,
        public readonly LogLevel $level,
        public readonly string   $service,
        public readonly string   $message,
        /** @var array<string, mixed> */
        public readonly array    $context = [],
        public readonly ?string  $traceId = null,
    ) {}

    /**
     * @param array<string, mixed> $data Предварительно валидированный массив лога
     */
    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: $data['timestamp'],
            level:     LogLevel::from($data['level']),
            service:   $data['service'],
            message:   $data['message'],
            context:   $data['context'] ?? [],
            traceId:   $data['trace_id'] ?? null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'timestamp' => $this->timestamp,
            'level'     => $this->level->value,
            'service'   => $this->service,
            'message'   => $this->message,
            'context'   => $this->context,
            'trace_id'  => $this->traceId,
        ];
    }
}
