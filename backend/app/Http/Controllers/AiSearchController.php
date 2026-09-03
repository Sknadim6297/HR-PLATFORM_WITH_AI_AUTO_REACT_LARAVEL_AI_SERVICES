<?php

namespace App\Http\Controllers;

use App\Exceptions\EmbeddingException;
use App\Http\Requests\AiSemanticSearchRequest;
use App\Http\Resources\AiSearchResultResource;
use App\Services\AI\EmbeddingService;
use App\Services\AI\VectorSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AiSearchController extends Controller
{
    public function __invoke(
        AiSemanticSearchRequest $request,
        EmbeddingService $embeddingService,
        VectorSearchService $vectorSearchService,
    ): JsonResponse|AnonymousResourceCollection {
        try {
            $queryVector = $embeddingService->embed($request->queryText());

            $results = $vectorSearchService->searchForUser(
                $request->user(),
                $queryVector,
                $request->documentId(),
                $request->topK(),
            );
        } catch (EmbeddingException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->isRetryable()
                ? Response::HTTP_BAD_GATEWAY
                : Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($results === null) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return AiSearchResultResource::collection($results)
            ->additional([
                'success' => true,
            ]);
    }
}
