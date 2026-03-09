<?php

declare(strict_types=1);

namespace App\Enum;

enum LogLevel: string
{
    case Emergency = 'emergency';
    case Alert     = 'alert';
    case Critical  = 'critical';
    case Error     = 'error';
    case Warning   = 'warning';
    case Notice    = 'notice';
    case Info      = 'info';
    case Debug     = 'debug';

    /**
     * Возвращает приоритет AMQP-сообщения (1–3).
     * Чем выше число — тем выше приоритет.
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::Emergency, self::Alert, self::Critical => 3,
            self::Error, self::Warning                   => 2,
            self::Notice, self::Info, self::Debug        => 1,
        };
    }

    /** @return list<string> */
    public static function allowedValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}