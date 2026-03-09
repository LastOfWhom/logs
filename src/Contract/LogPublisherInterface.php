<?php

declare(strict_types=1);

namespace App\Contract;

use App\DTO\LogEntryDTO;
use App\Exception\PublishException;

interface LogPublisherInterface
{
    /**
     * Публикует батч записей логов в брокер сообщений
     * Каждая запись отправляется отдельным сообщением
     *
     * @param list<LogEntryDTO> $logs
     *
     * @throws PublishException
     */
    public function publishBatch(array $logs, string $batchId): void;
}
