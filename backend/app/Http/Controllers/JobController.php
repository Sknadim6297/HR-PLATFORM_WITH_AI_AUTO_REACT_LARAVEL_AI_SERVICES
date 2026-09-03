<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Resources\JobResource;
use App\Models\Job;
use App\Services\Recruitment\JobService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class JobController extends Controller
{
    public function __construct(private readonly JobService $jobService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Job::class);

        $jobs = $this->jobService->listForUser($request->user(), $request->only([
            'status', 'department', 'employment_type', 'search', 'from', 'to', 'per_page',
        ]));

        return JobResource::collection($jobs)->additional(['success' => true]);
    }

    public function store(StoreJobRequest $request): JsonResponse
    {
        $job = $this->jobService->create($request->user(), $request->payload());

        return (new JobResource($job))
            ->additional(['success' => true, 'message' => 'Job created.'])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, Job $job): JobResource|JsonResponse
    {
        if ($request->user()->cannot('view', $job)) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $job->load('creator:id,name,email');

        return (new JobResource($job))->additional(['success' => true]);
    }

    public function update(UpdateJobRequest $request, Job $job): JobResource
    {
        $job = $this->jobService->update($request->user(), $job, $request->validated());

        return (new JobResource($job))->additional(['success' => true, 'message' => 'Job updated.']);
    }

    public function destroy(Request $request, Job $job): JsonResponse
    {
        $this->authorize('delete', $job);
        $this->jobService->delete($request->user(), $job);

        return response()->json([
            'success' => true,
            'message' => 'Job deleted.',
        ]);
    }

    public function publish(Request $request, Job $job): JobResource|JsonResponse
    {
        $this->authorize('publish', $job);

        if ($job->status === JobStatus::Published) {
            return (new JobResource($job->load('creator:id,name,email')))
                ->additional(['success' => true, 'message' => 'Job already published.']);
        }

        $job = $this->jobService->publish($request->user(), $job);

        return (new JobResource($job))->additional(['success' => true, 'message' => 'Job published.']);
    }

    public function close(Request $request, Job $job): JobResource
    {
        $this->authorize('close', $job);
        $job = $this->jobService->close($request->user(), $job);

        return (new JobResource($job))->additional(['success' => true, 'message' => 'Job closed.']);
    }
}
