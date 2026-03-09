<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\LogValidatorInterface;
use App\Enum\LogLevel;
use App\Exception\ValidationException;

/**
 * Валидирует сырые данные логов по бизнес-правилам
 */
final class LogValidatorService implements LogValidatorInterface
{
    private const MAX_BATCH_SIZE = 1000;

    /** Поля, обязательные к заполнению в каждой записи лога. */
    private const REQUIRED_FIELDS = [
        'timestamp',
        'level',
        'service',
        'message'
    ];

    public function validateBatch(array $logs): void
    {
        if ($logs === []) {
            throw new ValidationException(['logs' => ['The logs array must not be empty']]);
        }

        if (count($logs) > self::MAX_BATCH_SIZE) {
            throw new ValidationException([
                'logs' => [sprintf(
                    'Batch size %d exceeds the maximum of %d logs per request',
                    count($logs),
                    self::MAX_BATCH_SIZE,
                )],
            ]);
        }

        $errors = [];

        foreach ($logs as $index => $log) {
            if (!is_array($log)) {
                $errors["logs[$index]"] = ['Each log entry must be a JSON object'];
                continue;
            }

            try {
                $this->validateEntry($log, $index);
            } catch (ValidationException $e) {
                foreach ($e->getErrors() as $field => $messages) {
                    $errors[$field] = $messages;
                }
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateEntry(array $log, int $index = 0): void
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!array_key_exists($field, $log)
                || (is_string($log[$field]) && trim($log[$field]) === '')
                || $log[$field] === null
            ) {
                $errors["logs[$index].$field"] = [
                    sprintf('Field "%s" is required and must not be empty', $field),
                ];
            }
        }

        if (isset($log['timestamp']) && !$this->isValidTimestamp($log['timestamp'])) {
            $errors["logs[$index].timestamp"][] =
                'Invalid timestamp format; expected ISO-8601 (e.g. 2026-02-26T10:30:45Z)';
        }

        if (isset($log['level']) && is_string($log['level']) && !$this->isValidLevel($log['level'])) {
            $errors["logs[$index].level"][] = sprintf(
                'Invalid log level "%s". Allowed values: %s',
                $log['level'],
                implode(', ', LogLevel::allowedValues()),
            );
        }

        if (array_key_exists('context', $log) && $log['context'] !== null && !is_array($log['context'])) {
            $errors["logs[$index].context"][] = 'Field "context" must be a JSON object or null';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    private function isValidTimestamp(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        try {
            new \DateTimeImmutable($value);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function isValidLevel(string $value): bool
    {
        return LogLevel::tryFrom($value) !== null;
    }
}
