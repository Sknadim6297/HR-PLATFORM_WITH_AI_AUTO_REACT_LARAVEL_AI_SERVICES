<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertCandidateProfileRequest;
use App\Http\Resources\CandidateProfileResource;
use App\Models\CandidateProfile;
use App\Services\Recruitment\CandidateProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CandidateProfileController extends Controller
{
    public function __construct(private readonly CandidateProfileService $profileService) {}

    public function showMe(Request $request): CandidateProfileResource|JsonResponse
    {
        $profile = $request->user()->candidateProfile;

        if ($profile === null) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('view', $profile);

        return (new CandidateProfileResource($profile->load('user:id,name,email')))
            ->additional(['success' => true]);
    }

    public function upsert(UpsertCandidateProfileRequest $request): JsonResponse
    {
        $profile = $this->profileService->upsert($request->user(), $request->validated());

        return (new CandidateProfileResource($profile))
            ->additional(['success' => true, 'message' => 'Profile saved.'])
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function show(Request $request, CandidateProfile $profile): CandidateProfileResource|JsonResponse
    {
        if ($request->user()->cannot('view', $profile)) {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return (new CandidateProfileResource($profile->load('user:id,name,email')))
            ->additional(['success' => true]);
    }
}
