<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->foreignId('corrected_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('correction_type', 40);
            $table->text('notes')->nullable();
            $table->json('before_payload')->nullable();
            $table->json('after_payload')->nullable();
            $table->timestamps();

            $table->index(['attendance_date', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_logs');
    }
};

