<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('exam_duration_days')->nullable()->after('owner_decided_at');
            $table->date('exam_date')->nullable()->after('exam_duration_days');
        });
    }

    public function down(): void
    {
        Schema::table('exam_requests', function (Blueprint $table) {
            $table->dropColumn(['exam_duration_days', 'exam_date']);
        });
    }
};
