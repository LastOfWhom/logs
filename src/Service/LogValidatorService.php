<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\LogValidatorInterface;
use App\Exception\ValidationException;
use App\Http\Request\LogEntryRequest;
use App\Http\Request\LogIngestRequest;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LogValidatorService implements LogValidatorInterface
{
    public function __construct(private readonly ValidatorInterface $validator) {}

    public function validateBatch(array $logs): void
    {
        $errors = [];

        $batchViolations = $this->validator->validate(new LogIngestRequest($logs));
        foreach ($batchViolations as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        foreach ($logs as $index => $log) {
            if (!is_array($log)) {
                $errors["logs[$index]"] = ['Each log entry must be a JSON object'];
                continue;
            }

            $violations = $this->validator->validate(LogEntryRequest::fromArray($log));
            foreach ($violations as $violation) {
                $errors["logs[$index].{$violation->getPropertyPath()}"][] = $violation->getMessage();
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }

    public function validateEntry(array $log, int $index = 0): void
    {
        $violations = $this->validator->validate(LogEntryRequest::fromArray($log));

        if (count($violations) === 0) {
            return;
        }

        $errors = [];
        foreach ($violations as $violation) {
            $errors["logs[$index].{$violation->getPropertyPath()}"][] = $violation->getMessage();
        }

        throw new ValidationException($errors);
    }
}
