<?php

namespace App\Http\Controllers;

use App\Enums\AiDocumentStatus;
use App\Http\Requests\AiDocumentUploadRequest;
use App\Http\Resources\AiDocumentResource;
use App\Jobs\ProcessAiDocument;
use App\Models\AiDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AiDocumentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $documents = $request->user()
            ->aiDocuments()
            ->withCount('chunks')
            ->latest()
            ->paginate(15);

        return AiDocumentResource::collection($documents)
            ->additional([
                'success' => true,
            ]);
    }

    public function store(AiDocumentUploadRequest $request): JsonResponse
    {
        $user = $request->user();
        $file = $request->uploadedFile();
        $directory = AiDocument::directoryFor($user->id);
        $filename = $this->generatedFilename($file);

        $path = $file->storeAs($directory, $filename, 'local');

        if ($path === false) {
            return response()->json([
                'success' => false,
                'message' => 'Document could not be stored.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $document = $user->aiDocuments()->create([
                'original_name' => $this->safeOriginalName($file),
                'file_path' => $path,
                'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'status' => AiDocumentStatus::Uploaded,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        ProcessAiDocument::dispatch($document->id);

        $document->loadCount('chunks');

        return (new AiDocumentResource($document))
            ->additional([
                'success' => true,
                'message' => 'Document uploaded successfully.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, int $id): JsonResponse|AiDocumentResource
    {
        $document = $request->user()->aiDocuments()->withCount('chunks')->find($id);

        if ($document === null) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return (new AiDocumentResource($document))
            ->additional([
                'success' => true,
            ]);
    }

    private function generatedFilename(UploadedFile $file): string
    {
        $extension = match ($file->getMimeType()) {
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'text/plain' => 'txt',
            default => $file->guessExtension() ?: 'bin',
        };

        return Str::uuid()->toString().'.'.$extension;
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = trim($name);

        if ($name === '' || $name === '.' || $name === '..') {
            $name = 'document';
        }

        return Str::limit($name, 255, '');
    }
}
