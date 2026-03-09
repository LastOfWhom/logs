<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\ValidationException;
use App\Service\LogValidatorService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LogValidatorTest extends TestCase
{
    private LogValidatorService $validator;

    protected function setUp(): void
    {
        $this->validator = new LogValidatorService();
    }

    // validateEntry — успешные сценарии

    #[Test]
    public function validateEntry_withMinimalValidData_passes(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validateEntry([
            'timestamp' => '2026-02-26T10:30:45Z',
            'level'     => 'error',
            'service'   => 'auth-service',
            'message'   => 'User authentication failed',
        ]);
    }

    #[Test]
    public function validateEntry_withAllFields_passes(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validateEntry([
            'timestamp' => '2026-02-26T10:30:45+03:00',
            'level'     => 'debug',
            'service'   => 'payment-service',
            'message'   => 'Processing payment',
            'context'   => ['order_id' => 42, 'amount' => 99.90],
            'trace_id'  => 'abc123',
        ]);
    }

    #[Test]
    public function validateEntry_withNullContext_passes(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validateEntry([
            'timestamp' => '2026-02-26T10:30:45Z',
            'level'     => 'info',
            'service'   => 'svc',
            'message'   => 'msg',
            'context'   => null,
        ]);
    }

    /** @return array<string, array{string}> */
    public static function validLogLevelProvider(): array
    {
        return [
            'emergency' => ['emergency'],
            'alert'     => ['alert'],
            'critical'  => ['critical'],
            'error'     => ['error'],
            'warning'   => ['warning'],
            'notice'    => ['notice'],
            'info'      => ['info'],
            'debug'     => ['debug'],
        ];
    }

    #[Test]
    #[DataProvider('validLogLevelProvider')]
    public function validateEntry_acceptsAllValidLogLevels(string $level): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validateEntry([
            'timestamp' => '2026-02-26T10:30:45Z',
            'level'     => $level,
            'service'   => 'svc',
            'message'   => 'msg',
        ]);
    }

    // validateEntry — ошибки валидации

    /** @return array<string, array{array<string, string>}> */
    public static function missingRequiredFieldProvider(): array
    {
        $base = [
            'timestamp' => '2026-02-26T10:30:45Z',
            'level'     => 'info',
            'service'   => 'svc',
            'message'   => 'msg',
        ];

        return [
            'missing timestamp' => [array_diff_key($base, ['timestamp' => ''])],
            'missing level'     => [array_diff_key($base, ['level' => ''])],
            'missing service'   => [array_diff_key($base, ['service' => ''])],
            'missing message'   => [array_diff_key($base, ['message' => ''])],
        ];
    }

    #[Test]
    #[DataProvider('missingRequiredFieldProvider')]
    public function validateEntry_withMissingRequiredField_throwsValidationException(array $log): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateEntry($log);
    }

    #[Test]
    public function validateEntry_withEmptyStringFields_throwsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateEntry([
            'timestamp' => '',
            'level'     => 'info',
            'service'   => '',
            'message'   => '',
        ]);
    }

    #[Test]
    public function validateEntry_withInvalidTimestamp_throwsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateEntry([
            'timestamp' => 'not-a-date-at-all!!@@##',
            'level'     => 'info',
            'service'   => 'svc',
            'message'   => 'msg',
        ]);
    }

    #[Test]
    public function validateEntry_withInvalidLogLevel_throwsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateEntry([
            'timestamp' => '2026-02-26T10:30:45Z',
            'level'     => 'verbose',
            'service'   => 'svc',
            'message'   => 'msg',
        ]);
    }

    #[Test]
    public function validateEntry_withInvalidLogLevel_errorMentionsInvalidLevel(): void
    {
        try {
            $this->validator->validateEntry([
                'timestamp' => '2026-02-26T10:30:45Z',
                'level'     => 'verbose',
                'service'   => 'svc',
                'message'   => 'msg',
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $allMessages = implode(' ', array_merge(...array_values($e->getErrors())));
            $this->assertStringContainsString('verbose', $allMessages);
        }
    }

    #[Test]
    public function validateEntry_withNonArrayContext_throwsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateEntry([
            'timestamp' => '2026-02-26T10:30:45Z',
            'level'     => 'info',
            'service'   => 'svc',
            'message'   => 'msg',
            'context'   => 'not-an-array',
        ]);
    }

    #[Test]
    public function validateEntry_errorsContainFieldName(): void
    {
        try {
            $this->validator->validateEntry(['level' => 'info', 'service' => 'svc', 'message' => 'msg']);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('timestamp', implode(' ', array_keys($e->getErrors())));
        }
    }

    // validateBatch

    #[Test]
    public function validateBatch_withEmptyArray_throwsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateBatch([]);
    }

    #[Test]
    public function validateBatch_withValidEntries_passes(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validateBatch([
            ['timestamp' => '2026-02-26T10:30:45Z', 'level' => 'error', 'service' => 'svc', 'message' => 'msg1'],
            ['timestamp' => '2026-02-26T10:30:46Z', 'level' => 'info',  'service' => 'svc', 'message' => 'msg2'],
        ]);
    }

    #[Test]
    public function validateBatch_exceedingMaxSize_throwsValidationException(): void
    {
        $logs = array_fill(0, 1001, [
            'timestamp' => '2026-02-26T10:30:45Z',
            'level'     => 'info',
            'service'   => 'svc',
            'message'   => 'msg',
        ]);

        try {
            $this->validator->validateBatch($logs);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('logs', $e->getErrors());
        }
    }

    #[Test]
    public function validateBatch_withExactMaxSize_passes(): void
    {
        $this->expectNotToPerformAssertions();

        $logs = array_fill(0, 1000, [
            'timestamp' => '2026-02-26T10:30:45Z',
            'level'     => 'info',
            'service'   => 'svc',
            'message'   => 'msg',
        ]);

        $this->validator->validateBatch($logs);
    }

    #[Test]
    public function validateBatch_withPartiallyInvalidEntries_collectsAllErrors(): void
    {
        try {
            $this->validator->validateBatch([
                ['timestamp' => '2026-02-26T10:30:45Z', 'level' => 'info', 'service' => 'svc', 'message' => 'ok'],
                ['timestamp' => '2026-02-26T10:30:46Z', 'level' => 'info', 'service' => 'svc'],
                ['timestamp' => '2026-02-26T10:30:47Z', 'level' => 'nope', 'service' => 'svc', 'message' => 'msg'],
            ]);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertCount(2, $e->getErrors());
        }
    }

    #[Test]
    public function validateBatch_withNonArrayEntry_throwsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $this->validator->validateBatch(['not-an-array']);
    }
}
