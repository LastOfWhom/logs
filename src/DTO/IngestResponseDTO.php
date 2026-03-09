<?php

declare(strict_types=1);

namespace App\DTO;

final class IngestResponseDTO
{
    public function __construct(
        public readonly string $status,
        public readonly string $batchId,
        public readonly int    $logsCount,
    ) {}
}
