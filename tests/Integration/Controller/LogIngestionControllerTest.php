<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Message\LogMessage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class LogIngestionControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->getTransport()->reset();
    }

    /** @param array<string, mixed> $body */
    private function post(array $body): void
    {
        $this->client->request(
            method:  'POST',
            uri:     '/api/logs/ingest',
            content: json_encode($body, JSON_THROW_ON_ERROR),
            server:  ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
        );
    }

    private function minimalLog(array $overrides = []): array
    {
        return array_merge([
            'timestamp' => '2026-02-26T10:30:45Z',
            'level'     => 'error',
            'service'   => 'auth-service',
            'message'   => 'User authentication failed',
        ], $overrides);
    }

    private function getTransport(): InMemoryTransport
    {
        return static::getContainer()->get('messenger.transport.async');
    }

    /** @return array<string, mixed> */
    private function decodeResponse(): array
    {
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);

        return $data;
    }

    // 202 Accepted — успешные сценарии

    public function testIngest_withSingleValidLog_returns202(): void
    {
        $this->post(['logs' => [$this->minimalLog()]]);

        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);

        $data = $this->decodeResponse();
        $this->assertSame('accepted', $data['status']);
        $this->assertSame(1, $data['logs_count']);
        $this->assertStringStartsWith('batch_', $data['batch_id']);
    }

    public function testIngest_withMultipleLogs_returnsCorrectCount(): void
    {
        $this->post(['logs' => [
            $this->minimalLog(['level' => 'error']),
            $this->minimalLog(['level' => 'info', 'message' => 'Request processed']),
        ]]);

        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        $this->assertSame(2, $this->decodeResponse()['logs_count']);
    }

    public function testIngest_publishesOneMessagePerLog(): void
    {
        $this->post(['logs' => [
            $this->minimalLog(['level' => 'error']),
            $this->minimalLog(['level' => 'info']),
            $this->minimalLog(['level' => 'debug']),
        ]]);

        $sent = $this->getTransport()->getSent();
        $this->assertCount(3, $sent, 'Each log entry should be dispatched as a separate message');

        foreach ($sent as $envelope) {
            $this->assertInstanceOf(LogMessage::class, $envelope->getMessage());
        }
    }

    public function testIngest_dispatchedMessagesContainCorrectBatchId(): void
    {
        $this->post(['logs' => [$this->minimalLog()]]);

        $batchId = $this->decodeResponse()['batch_id'];

        $sent = $this->getTransport()->getSent();
        $this->assertCount(1, $sent);

        $message = $sent[0]->getMessage();
        $this->assertInstanceOf(LogMessage::class, $message);
        $this->assertSame($batchId, $message->getBatchId());
    }

    public function testIngest_batchIdIsUniquePerRequest(): void
    {
        $body    = json_encode(['logs' => [$this->minimalLog()]], JSON_THROW_ON_ERROR);
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];

        $this->client->request('POST', '/api/logs/ingest', [], [], $headers, $body);
        $id1 = $this->decodeResponse()['batch_id'];

        $this->client->request('POST', '/api/logs/ingest', [], [], $headers, $body);
        $id2 = $this->decodeResponse()['batch_id'];

        $this->assertNotSame($id1, $id2);
    }

    public function testIngest_withAllLogLevels_returns202(): void
    {
        $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

        $this->post(['logs' => array_map(fn(string $lvl) => $this->minimalLog(['level' => $lvl]), $levels)]);

        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
        $this->assertSame(count($levels), $this->decodeResponse()['logs_count']);
    }

    public function testIngest_withOptionalFields_returns202(): void
    {
        $this->post([
            'logs' => [
                array_merge($this->minimalLog(), [
                    'context'  => ['user_id' => 123, 'ip' => '192.168.1.1'],
                    'trace_id' => 'abc123def456',
                ]),
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
    }

    public function testIngest_responseBodyHasExpectedShape(): void
    {
        $this->post(['logs' => [$this->minimalLog()]]);

        $data = $this->decodeResponse();
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('batch_id', $data);
        $this->assertArrayHasKey('logs_count', $data);
    }

    // 400 Bad Request — ошибка запроса

    public function testIngest_withInvalidJson_returns400(): void
    {
        $this->client->request(
            method:  'POST',
            uri:     '/api/logs/ingest',
            content: '{invalid json',
            server:  ['CONTENT_TYPE' => 'application/json'],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('error', $this->decodeResponse()['status']);
    }

    public function testIngest_withMissingLogsKey_returns400(): void
    {
        $this->post(['data' => []]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testIngest_withEmptyLogsArray_returns400(): void
    {
        $this->post(['logs' => []]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey('errors', $this->decodeResponse());
    }

    /** @return array<string, array{array<string, string>}> */
    public static function missingRequiredFieldProvider(): array
    {
        return [
            'missing timestamp' => [['level' => 'info', 'service' => 'svc', 'message' => 'msg']],
            'missing level'     => [['timestamp' => '2026-02-26T10:30:45Z', 'service' => 'svc', 'message' => 'msg']],
            'missing service'   => [['timestamp' => '2026-02-26T10:30:45Z', 'level' => 'info', 'message' => 'msg']],
            'missing message'   => [['timestamp' => '2026-02-26T10:30:45Z', 'level' => 'info', 'service' => 'svc']],
        ];
    }

    #[DataProvider('missingRequiredFieldProvider')]
    public function testIngest_withMissingRequiredField_returns400(array $log): void
    {
        $this->post(['logs' => [$log]]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->decodeResponse();
        $this->assertSame('error', $data['status']);
        $this->assertNotEmpty($data['errors']);
    }

    public function testIngest_withInvalidLogLevel_returns400(): void
    {
        $this->post(['logs' => [$this->minimalLog(['level' => 'verbose'])]]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey('errors', $this->decodeResponse());
    }

    public function testIngest_with1001Logs_returns400(): void
    {
        $this->post(['logs' => array_fill(0, 1001, $this->minimalLog())]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = $this->decodeResponse();
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('1000', $data['errors']['logs'][0]);
    }

    public function testIngest_with1000Logs_returns202(): void
    {
        $this->post(['logs' => array_fill(0, 1000, $this->minimalLog())]);

        $this->assertResponseStatusCodeSame(Response::HTTP_ACCEPTED);
    }

    public function testIngest_withMultipleInvalidLogs_returnsAllErrors(): void
    {
        $this->post([
            'logs' => [
                ['timestamp' => '2026-02-26T10:30:45Z', 'level' => 'info', 'service' => 'svc', 'message' => 'ok'],
                ['level' => 'info', 'service' => 'svc', 'message' => 'missing timestamp'],
                ['timestamp' => '2026-02-26T10:30:45Z', 'service' => 'svc', 'message' => 'missing level'],
            ],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertGreaterThanOrEqual(2, count($this->decodeResponse()['errors']));
    }

    public function testIngest_responseContentTypeIsJson(): void
    {
        $this->post(['logs' => [$this->minimalLog()]]);

        $this->assertStringContainsString(
            'application/json',
            $this->client->getResponse()->headers->get('content-type') ?? '',
        );
    }
}
