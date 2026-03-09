<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Бросается когда сообщение не удалось опубликовать в брокер
 */
final class PublishException extends \RuntimeException
{
    public function __construct(
        string $message = 'Failed to publish message to broker',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
