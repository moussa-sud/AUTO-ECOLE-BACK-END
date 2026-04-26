<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'manager_reviewed', 'approved', 'rejected'])->default('pending');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('manager_recommendation', ['approved', 'rejected'])->nullable();
            $table->text('manager_notes')->nullable();
            $table->timestamp('manager_reviewed_at')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('owner_decision', ['approved', 'rejected'])->nullable();
            $table->text('owner_notes')->nullable();
            $table->timestamp('owner_decided_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['user_id']);
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
        Schema::dropIfExists('exam_requests');
    }
};
