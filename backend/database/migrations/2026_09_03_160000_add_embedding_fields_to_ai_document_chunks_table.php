<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_document_chunks', function (Blueprint $table) {
            $table->longText('embedding')->nullable()->after('metadata');
            $table->string('embedding_model', 128)->nullable()->after('embedding');
            $table->timestamp('embedded_at')->nullable()->after('embedding_model');

            $table->index('embedded_at');
        });
    }

    public function down(): void
    {
        Schema::table('ai_document_chunks', function (Blueprint $table) {
            $table->dropIndex(['embedded_at']);
            $table->dropColumn(['embedding', 'embedding_model', 'embedded_at']);
        });
    }
};
