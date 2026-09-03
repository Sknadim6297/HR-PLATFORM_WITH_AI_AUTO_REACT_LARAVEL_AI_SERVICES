<?php

namespace App\Services\AI;

use App\Enums\AiTask;

class AiPromptBuilder
{
    /**
     * @return list<array{role: string, content: string}>
     */
    public function messages(AiTask $task, string $content): array
    {
        return [
            [
                'role' => 'system',
                'content' => $this->systemPrompt($task),
            ],
            [
                'role' => 'user',
                'content' => $this->userPrompt($task, $content),
            ],
        ];
    }

    private function systemPrompt(AiTask $task): string
    {
        $base = 'You are an AI assistant for a professional organizational HR platform. '
            .'You must respond with a single valid JSON object only. '
            .'Do not include markdown, code fences, or any text outside JSON.';

        $schema = match ($task) {
            AiTask::Summarize => 'Return JSON with this exact shape: {"summary":"string"}. '
                .'The summary must be concise, professional, and faithful to the source text.',
            AiTask::Generate => 'Return JSON with this exact shape: {"content":"string"}. '
                .'The content must be useful, professional business writing based on the request.',
            AiTask::Classify => 'Return JSON with this exact shape: {"category":"string","confidence":0.0}. '
                .'category must be a short business/HR label. '
                .'confidence must be a number between 0 and 1 inclusive.',
        };

        return $base.' '.$schema;
    }

    private function userPrompt(AiTask $task, string $content): string
    {
        $instruction = match ($task) {
            AiTask::Summarize => 'Summarize the following text as a concise professional summary.',
            AiTask::Generate => 'Generate useful professional business content based on the following input.',
            AiTask::Classify => 'Classify the following text into a concise business/HR category and provide a confidence score.',
        };

        return $instruction."\n\n".$content;
    }
}
