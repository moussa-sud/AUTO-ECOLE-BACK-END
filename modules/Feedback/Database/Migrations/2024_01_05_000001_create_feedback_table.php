<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');   // FK → users (role: student)
            $table->unsignedBigInteger('staff_id');     // FK → users (role: manager/owner)
            $table->tinyInteger('rating');              // 1 – 5 stars
            $table->text('comment')->nullable();
            $table->timestamps();

            // One review per student per staff member
            $table->unique(['tenant_id', 'student_id', 'staff_id']);

            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
