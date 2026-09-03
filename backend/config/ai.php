<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document Chunking
    |--------------------------------------------------------------------------
    |
    | chunk_size and overlap are measured in words. Overlap must be smaller
    | than chunk_size. token_count is left null unless a real tokenizer is added.
    |
    */

    'document_chunking' => [
        'chunk_size' => (int) env('AI_DOCUMENT_CHUNK_SIZE', 700),
        'overlap' => (int) env('AI_DOCUMENT_CHUNK_OVERLAP', 100),
    ],

];
