<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('time_slot_id')->constrained('time_slots')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Prevent a student from booking the same slot on the same date
            $table->unique(['time_slot_id', 'date', 'student_id', 'tenant_id'], 'unique_student_slot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
