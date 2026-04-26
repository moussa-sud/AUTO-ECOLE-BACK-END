<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->json('working_days')->default('["monday","tuesday","wednesday","thursday","friday","saturday"]');
            $table->time('working_hours_start')->default('08:00:00');
            $table->time('working_hours_end')->default('18:00:00');
            $table->integer('slot_duration_minutes')->default(60);
            $table->integer('max_students_per_slot')->default(1);
            $table->integer('cancellation_hours')->default(24);
            $table->timestamps();

            $table->unique('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_settings');
    }
};
