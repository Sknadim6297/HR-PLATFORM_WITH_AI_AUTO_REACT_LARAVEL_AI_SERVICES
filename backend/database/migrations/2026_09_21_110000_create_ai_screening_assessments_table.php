<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_screening_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('job_applications')->cascadeOnDelete();
            $table->string('recommendation', 32);
            $table->unsignedTinyInteger('score');
            $table->text('reasoning')->nullable();
            $table->string('confidence', 16)->nullable();
            $table->string('model')->nullable();
            $table->timestamp('screened_at')->nullable();
            $table->timestamps();

            $table->index('recommendation');
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_screening_assessments');
    }
};