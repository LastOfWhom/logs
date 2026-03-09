<?php

declare(strict_types=1);

namespace App\Contract;

use App\DTO\IngestResponseDTO;
use App\Exception\PublishException;
use App\Exception\ValidationException;

interface LogIngestionServiceInterface
{
    /**
     * Валидирует и публикует сырой батч логов.
     *
     * @param array<string, mixed> $payload Декодированное тело запроса
     *
     * @throws ValidationException При невалидной структуре или содержимом
     * @throws PublishException    При ошибке связи с брокером
     */
    public function ingest(array $payload): IngestResponseDTO;
}
