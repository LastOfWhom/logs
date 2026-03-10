<?php

declare(strict_types=1);

namespace App\Http\Request;

use App\Enum\LogLevel;
use Symfony\Component\Validator\Constraints as Assert;

final class LogEntryRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Field "timestamp" is required and must not be empty')]
        #[Assert\Regex(
            pattern: '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            message: 'Invalid timestamp format e.g. ( 2026-02-26T10:30:45Z)',
        )]
        public readonly ?string $timestamp = null,

        #[Assert\NotBlank(message: 'Field "level" is required and must not be empty')]
        #[Assert\Choice(
            callback: [LogLevel::class, 'allowedValues'],
            message: 'Invalid log level "{{ value }}". Allowed values: emergency, alert, critical, error, warning, notice, info, debug',
        )]
        public readonly ?string $level = null,

        #[Assert\NotBlank(message: 'Field "service" is required and must not be empty')]
        #[Assert\Length(max: 255)]
        public readonly ?string $service = null,

        #[Assert\NotBlank(message: 'Field "message" is required and must not be empty')]
        #[Assert\Length(max: 10000)]
        public readonly ?string $message = null,

        #[Assert\Type(type: 'array', message: 'Field "context" must be a JSON object or null')]
        public readonly mixed $context = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            timestamp: isset($data['timestamp']) && is_string($data['timestamp']) ? $data['timestamp'] : null,
            level:     isset($data['level']) && is_string($data['level']) ? $data['level'] : null,
            service:   isset($data['service']) && is_string($data['service']) ? $data['service'] : null,
            message:   isset($data['message']) && is_string($data['message']) ? $data['message'] : null,
            context:   $data['context'] ?? null,
        );
    }
}
