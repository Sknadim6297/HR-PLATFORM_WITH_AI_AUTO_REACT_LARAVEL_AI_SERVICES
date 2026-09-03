<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('department')->nullable();
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('responsibilities')->nullable();
            $table->string('employment_type', 32);
            $table->string('location')->nullable();
            $table->unsignedInteger('salary_min')->nullable();
            $table->unsignedInteger('salary_max')->nullable();
            $table->unsignedTinyInteger('experience_min')->nullable();
            $table->unsignedTinyInteger('experience_max')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closing_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_by');
            $table->index('published_at');
            $table->index('employment_type');
        });

        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->string('headline')->nullable();
            $table->unsignedTinyInteger('years_of_experience')->nullable();
            $table->string('current_company')->nullable();
            $table->string('current_role')->nullable();
            $table->text('education_summary')->nullable();
            $table->json('skills')->nullable();
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('job_postings')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('resume_document_id')->nullable()->constrained('ai_documents')->nullOnDelete();
            $table->text('cover_letter')->nullable();
            $table->string('status', 32)->default('applied');
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->unique(['candidate_id', 'job_id']);
            $table->index('job_id');
            $table->index('candidate_id');
            $table->index('status');
            $table->index('applied_at');
        });

        Schema::create('ai_resume_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('job_applications')->cascadeOnDelete();
            $table->text('summary')->nullable();
            $table->json('skills')->nullable();
            $table->json('experience')->nullable();
            $table->json('education')->nullable();
            $table->json('strengths')->nullable();
            $table->json('gaps')->nullable();
            $table->string('confidence', 16)->nullable();
            $table->string('model')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_job_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->unique()->constrained('job_applications')->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->json('matched_skills')->nullable();
            $table->json('missing_skills')->nullable();
            $table->text('reasoning')->nullable();
            $table->string('confidence', 16)->nullable();
            $table->string('model')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('action');
            $table->index('created_at');
        });

        Schema::create('automation_events', function (Blueprint $table) {
            $table->id();
            $table->string('workflow');
            $table->string('event_key')->unique();
            $table->string('status', 32)->default('pending');
            $table->json('payload')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('workflow');
            $table->index('status');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('automation_events');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('ai_job_matches');
        Schema::dropIfExists('ai_resume_analyses');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('candidate_profiles');
        Schema::dropIfExists('job_postings');
    }
};
