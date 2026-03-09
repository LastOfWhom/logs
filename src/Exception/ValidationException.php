<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Бросается при ошибке валидации входных данных
 */
final class ValidationException extends \RuntimeException
{
    /**
     * @param array<string, list<string>> $errors Ошибки валидации, сгруппированные по полям
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Validation failed',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @return array<string, list<string>> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
