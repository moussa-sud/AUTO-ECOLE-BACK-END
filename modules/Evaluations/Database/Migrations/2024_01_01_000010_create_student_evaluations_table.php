<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');

            // Skill scores — 1 (ضعيف جداً) to 5 (ممتاز)
            $table->tinyInteger('parking_score')->nullable()->unsigned();
            $table->tinyInteger('reverse_score')->nullable()->unsigned();
            $table->tinyInteger('city_driving_score')->nullable()->unsigned();
            $table->tinyInteger('steering_score')->nullable()->unsigned();
            $table->tinyInteger('rules_score')->nullable()->unsigned();
            $table->tinyInteger('confidence_score')->nullable()->unsigned();

            $table->text('comment')->nullable();
            $table->enum('final_status', ['ready', 'not_ready'])->nullable();

            $table->unsignedBigInteger('evaluated_by')->nullable();
            $table->timestamps();

            // One evaluation record per student per school (upsert on update)
            $table->unique(['tenant_id', 'student_id']);

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_evaluations');
    }
};
