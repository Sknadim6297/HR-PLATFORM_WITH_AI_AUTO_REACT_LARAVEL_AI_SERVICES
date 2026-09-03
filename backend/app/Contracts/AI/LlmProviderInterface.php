<?php

namespace App\Contracts\AI;

interface LlmProviderInterface
{
    /**
     * Generate a model completion from system and user prompts.
     *
     * @param  array<string, mixed>  $options
     * @return array{content: string, model: string}
     */
    public function generate(string $systemPrompt, string $userPrompt, array $options = []): array;
}
