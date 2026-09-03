<?php

namespace App\Http\Controllers;

use App\Enums\AiWorkflowStatus;
use App\Http\Requests\AiWorkflowRequest;
use App\Jobs\ProcessAiWorkflow;
use App\Services\AI\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIController extends Controller
{
    public function generate(
        Request $request,
        AIService $aiService
    ): JsonResponse {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:5000'],
        ]);

        $result = $aiService->generate($validated['prompt']);

        return response()->json($result);
    }

    public function workflow(AiWorkflowRequest $request): JsonResponse
    {
        $workflow = $request->user()->aiWorkflows()->create([
            'task' => $request->task(),
            'content' => $request->content(),
            'status' => AiWorkflowStatus::Pending,
        ]);

        ProcessAiWorkflow::dispatch($workflow->id);

        return response()->json([
            'success' => true,
            'message' => 'AI workflow has been queued.',
            'workflow_id' => $workflow->id,
            'status' => $workflow->status->value,
        ], 202);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $workflow = $request->user()->aiWorkflows()->find($id);

        if ($workflow === null) {
            return response()->json([
                'success' => false,
                'message' => 'Workflow not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'workflow' => [
                'id' => $workflow->id,
                'task' => $workflow->task->value,
                'status' => $workflow->status->value,
                'result' => $workflow->result,
                'error_message' => $workflow->error_message,
            ],
        ]);
    }
}
