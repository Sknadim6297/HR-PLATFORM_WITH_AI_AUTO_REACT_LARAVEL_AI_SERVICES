<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\AiDocumentController;
use App\Http\Controllers\AiRagController;
use App\Http\Controllers\AiSearchController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\CandidateProfileController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/demo/admin', function () {
        return response()->json([
            'message' => 'Admin access granted.',
        ]);
    })->middleware('role:admin');

    Route::get('/demo/staff', function () {
        return response()->json([
            'message' => 'Admin or HR access granted.',
        ]);
    })->middleware('role:admin,hr');

    Route::get('/demo/candidate', function () {
        return response()->json([
            'message' => 'Candidate access granted.',
        ]);
    })->middleware('role:candidate');

    Route::post('/ai/workflow', [AIController::class, 'workflow'])->middleware('throttle:ai');
    Route::get('/ai/workflow/{id}', [AIController::class, 'show']);

    Route::post('/ai/documents', [AiDocumentController::class, 'store'])->middleware('throttle:ai');
    Route::get('/ai/documents', [AiDocumentController::class, 'index']);
    Route::get('/ai/documents/{id}', [AiDocumentController::class, 'show']);

    Route::post('/ai/search', AiSearchController::class)->middleware('throttle:ai');
    Route::post('/ai/ask', AiRagController::class)->middleware('throttle:ai');

    Route::get('/jobs', [JobController::class, 'index']);
    Route::post('/jobs', [JobController::class, 'store']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);
    Route::put('/jobs/{job}', [JobController::class, 'update']);
    Route::delete('/jobs/{job}', [JobController::class, 'destroy']);
    Route::post('/jobs/{job}/publish', [JobController::class, 'publish']);
    Route::post('/jobs/{job}/close', [JobController::class, 'close']);

    Route::get('/candidate/profile', [CandidateProfileController::class, 'showMe']);
    Route::put('/candidate/profile', [CandidateProfileController::class, 'upsert']);
    Route::get('/candidate/profiles/{profile}', [CandidateProfileController::class, 'show']);

    Route::post('/jobs/{job}/applications', [JobApplicationController::class, 'store']);
    Route::get('/jobs/{job}/applications', [JobApplicationController::class, 'indexForJob']);
    Route::get('/applications', [JobApplicationController::class, 'index']);
    Route::get('/applications/{application}', [JobApplicationController::class, 'show']);
    Route::patch('/applications/{application}/status', [JobApplicationController::class, 'updateStatus']);
    Route::post('/applications/{application}/ai-screen', [JobApplicationController::class, 'aiScreen'])
        ->middleware('throttle:ai');

    Route::get('/notifications', [NotificationController::class, 'index']);
});

Route::post('/ai/generate', [AIController::class, 'generate'])->middleware('throttle:ai');
