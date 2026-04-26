<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('url');
            $table->text('description')->nullable();
            $table->integer('duration')->default(0)->comment('Duration in seconds');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained()->onDelete('cascade');
            $table->text('question_text');
            $table->string('image')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->string('text');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        Schema::create('student_series_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('series_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->boolean('video_watched')->default(false);
            $table->timestamp('video_watched_at')->nullable();
            $table->boolean('quiz_completed')->default(false);
            $table->timestamp('quiz_completed_at')->nullable();
            $table->integer('best_score')->default(0);
            $table->integer('attempts_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'series_id']);
            $table->index(['tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_series_progress');
        Schema::dropIfExists('answers');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('videos');
        Schema::dropIfExists('series');
    }
};
