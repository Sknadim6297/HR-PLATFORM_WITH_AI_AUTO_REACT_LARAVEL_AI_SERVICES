<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\AiDocumentController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Step 4 — authorization demo routes (temporary verification only)
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

    Route::post('/ai/workflow', [AIController::class, 'workflow']);
    Route::get('/ai/workflow/{id}', [AIController::class, 'show']);

    Route::post('/ai/documents', [AiDocumentController::class, 'store']);
    Route::get('/ai/documents', [AiDocumentController::class, 'index']);
    Route::get('/ai/documents/{id}', [AiDocumentController::class, 'show']);
});

Route::post('/ai/generate', [AIController::class, 'generate']);
