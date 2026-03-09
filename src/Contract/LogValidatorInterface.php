<?php

declare(strict_types=1);

namespace App\Contract;

use App\Exception\ValidationException;

interface LogValidatorInterface
{
    /**
     * Валидирует весь батч сырых записей логов.
     *
     * @param array<int, array<string, mixed>> $logs
     *
     * @throws ValidationException
     */
    public function validateBatch(array $logs): void;

    /**
     * Валидирует одну сырую запись лога.
     *
     * @param array<string, mixed> $log
     *
     * @throws ValidationException
     */
    public function validateEntry(array $log, int $index = 0): void;
}
