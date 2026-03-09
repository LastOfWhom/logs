<?php

declare(strict_types=1);

namespace App\Contract;

interface BatchIdGeneratorInterface
{
    /**
     * Генерирует уникальный идентификатор батча с префиксом "batch_"
     */
    public function generate(): string;
}
