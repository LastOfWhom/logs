<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\BatchIdGeneratorInterface;
use App\Contract\LogIngestionServiceInterface;
use App\Contract\LogPublisherInterface;
use App\Contract\LogValidatorInterface;
use App\DTO\IngestResponseDTO;
use App\DTO\LogEntryDTO;

/**
 * Класс намеренно не содержит бизнес-логики —
 * каждая ответственность делегирована отдельному коллаборатору (SRP + DIP).
 */
final class LogIngestionService implements LogIngestionServiceInterface
{
    public function __construct(
        private readonly LogValidatorInterface    $validator,
        private readonly LogPublisherInterface    $publisher,
        private readonly BatchIdGeneratorInterface $batchIdGenerator,
    ) {}

    public function ingest(array $payload): IngestResponseDTO
    {
        /** @var array<int, array<string, mixed>> $rawLogs */
        $rawLogs = $payload['logs'] ?? [];

        $this->validator->validateBatch($rawLogs);

        $batchId = $this->batchIdGenerator->generate();

        $logDTOs = array_map(
            static fn(array $raw): LogEntryDTO => LogEntryDTO::fromArray($raw),
            $rawLogs,
        );

        $this->publisher->publishBatch($logDTOs, $batchId);

        return new IngestResponseDTO(
            status:    'accepted',
            batchId:   $batchId,
            logsCount: count($logDTOs),
        );
    }
}
