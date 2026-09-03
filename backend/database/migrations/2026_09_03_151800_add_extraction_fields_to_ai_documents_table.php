<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_documents', function (Blueprint $table) {
            $table->longText('extracted_text')->nullable()->after('error_message');
            $table->timestamp('processed_at')->nullable()->after('extracted_text');
        });
    }

    public function down(): void
    {
        Schema::table('ai_documents', function (Blueprint $table) {
            $table->dropColumn(['extracted_text', 'processed_at']);
        });
    }
};
