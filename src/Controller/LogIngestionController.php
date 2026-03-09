<?php

declare(strict_types=1);

namespace App\Controller;

use App\Contract\LogIngestionServiceInterface;
use App\Exception\PublishException;
use App\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/logs', name: 'api_logs_')]
final class LogIngestionController extends AbstractController
{
    public function __construct(
        private readonly LogIngestionServiceInterface $ingestionService,
    ) {}

    #[Route('/ingest', name: 'ingest', methods: ['POST'])]
    public function ingest(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), associative: true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(
                [
                    'status'  => 'error',
                    'message' => 'Request body must be valid JSON',
                    'errors'  => [
                        'body' => [json_last_error_msg()],
                    ],
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $response = $this->ingestionService->ingest($payload ?? []);
        } catch (ValidationException $e) {
            return $this->json(
                [
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'errors'  => $e->getErrors(),
                ],
                Response::HTTP_BAD_REQUEST,
            );
        } catch (PublishException $e) {
            return $this->json(
                [
                    'status'  => 'error',
                    'message' => 'Unable to process logs at this time. Please retry later.',
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return $this->json(
            [
                'status'    => $response->status,
                'batch_id'  => $response->batchId,
                'logs_count' => $response->logsCount,
            ],
            Response::HTTP_ACCEPTED,
        );
    }
}
