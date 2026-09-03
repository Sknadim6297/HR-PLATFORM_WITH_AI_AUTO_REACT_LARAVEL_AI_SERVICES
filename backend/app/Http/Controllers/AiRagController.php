<?php

namespace App\Http\Controllers;

use App\Exceptions\EmbeddingException;
use App\Exceptions\LlmProviderException;
use App\Exceptions\OwnedResourceNotFoundException;
use App\Http\Requests\AiRagRequest;
use App\Http\Resources\AiRagResponseResource;
use App\Services\AI\RagService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * POST /api/ai/ask
 *
 * Authenticated RAG endpoint.
 *
 * Body:
 * - question (required string)
 * - document_id (optional, must belong to the authenticated user)
 * - conversation_id (optional, must belong to the authenticated user)
 * - top_k (optional, 1-20)
 *
 * Behavior:
 * - Embeds the question, retrieves relevant owned completed-document chunks,
 *   then answers via the configured LLM using retrieved context only.
 * - Creates a conversation when conversation_id is omitted.
 * - Returns 404 for foreign/missing documents or conversations (no existence leak).
 * - Skips the LLM when no relevant chunks are found.
 */
class AiRagController extends Controller
{
    public function __invoke(AiRagRequest $request, RagService $ragService): JsonResponse|AiRagResponseResource
    {
        try {
            $result = $ragService->ask(
                $request->user(),
                $request->question(),
                $request->documentId(),
                $request->topK(),
                $request->conversationId(),
            );
        } catch (OwnedResourceNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        } catch (EmbeddingException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->isRetryable()
                ? Response::HTTP_BAD_GATEWAY
                : Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (LlmProviderException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->isRetryable()
                ? Response::HTTP_BAD_GATEWAY
                : Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return (new AiRagResponseResource($result))
            ->additional([
                'success' => true,
            ]);
    }
}
