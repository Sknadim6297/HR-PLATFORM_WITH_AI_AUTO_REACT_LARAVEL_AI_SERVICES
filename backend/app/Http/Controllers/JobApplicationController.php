<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidApplicationTransitionException;
use App\Exceptions\LlmProviderException;
use App\Exceptions\OwnedResourceNotFoundException;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\JobApplicationResource;
use App\Models\Job;
use App\Models\JobApplication;
use App\Services\AI\AiScreeningService;
use App\Services\Recruitment\ApplicationService;
use App\Services\Recruitment\ApplicationStatusService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class JobApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly ApplicationStatusService $statusService,
        private readonly AiScreeningService $screeningService,
    ) {}

    public function store(StoreApplicationRequest $request, Job $job): JsonResponse
    {
        try {
            $application = $this->applicationService->apply(
                $request->user(),
                $job,
                $request->validated(),
            );
        } catch (OwnedResourceNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        } catch (ConflictHttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_CONFLICT);
        }

        return (new JobApplicationResource($application))
            ->additional(['success' => true, 'message' => 'Application submitted.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', JobApplication::class);

        $applications = $this->applicationService->listForUser($request->user(), $request->only([
            'status', 'job_id', 'candidate_id', 'min_score', 'max_score', 'from', 'to', 'search', 'per_page',
        ]));

        return JobApplicationResource::collection($applications)->additional(['success' => true]);
    }

    public function indexForJob(Request $request, Job $job): AnonymousResourceCollection|JsonResponse
    {
        if ($request->user()->cannot('viewApplications', $job)) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $applications = $this->applicationService->listForJob($request->user(), $job, $request->only([
            'status', 'candidate_id', 'min_score', 'max_score', 'from', 'to', 'search', 'per_page',
        ]));

        return JobApplicationResource::collection($applications)->additional(['success' => true]);
    }

    public function show(Request $request, int $application): JobApplicationResource|JsonResponse
    {
        try {
            $model = $this->applicationService->findAccessible($request->user(), $application);
        } catch (OwnedResourceNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }

        if ($request->user()->cannot('view', $model)) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return (new JobApplicationResource($model))->additional(['success' => true]);
    }

    public function updateStatus(
        UpdateApplicationStatusRequest $request,
        JobApplication $application,
    ): JobApplicationResource|JsonResponse {
        if ($request->user()->cannot('updateStatus', $application)) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $updated = $this->statusService->transition(
                $request->user(),
                $application,
                $request->status(),
            );
        } catch (OwnedResourceNotFoundException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        } catch (InvalidApplicationTransitionException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return (new JobApplicationResource($updated))
            ->additional(['success' => true, 'message' => 'Application status updated.']);
    }

    public function aiScreen(Request $request, JobApplication $application): JsonResponse
    {
        try {
            $this->authorize('screen', $application);
        } catch (AuthorizationException) {
            return response()->json([
                'success' => false,
                'message' => 'Application not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $result = $this->screeningService->screen($application);
        } catch (LlmProviderException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->isRetryable()
                ? Response::HTTP_BAD_GATEWAY
                : Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
            'meta' => [
                'decision_support_only' => true,
                'message' => 'AI screening is advisory. Final hiring decisions require authorized HR/Admin action.',
            ],
        ]);
    }
}
