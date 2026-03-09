<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\BatchIdGeneratorInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Генерирует уникальные идентификаторы батча на основе UUID v4
 * Формат: batch_<32 hex символа>
 */
final class BatchIdGeneratorService implements BatchIdGeneratorInterface
{
    private const PREFIX = 'batch_';

    public function generate(): string
    {
        return self::PREFIX . str_replace('-', '', Uuid::v4()->toRfc4122());
    }
}
