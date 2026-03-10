<?php

declare(strict_types=1);

namespace App\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class LogIngestRequest
{
    public function __construct(
        #[Assert\Count(
            min: 1,
            max: 1000,
            minMessage: 'The logs array must not be empty',
            maxMessage: 'Batch size {{ count }} exceeds the maximum of {{ limit }} logs per request',
        )]
        public readonly array $logs,
    ) {}
}
