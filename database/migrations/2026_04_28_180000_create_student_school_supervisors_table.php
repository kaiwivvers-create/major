<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_school_supervisors')) {
            return;
        }

        Schema::create('student_school_supervisors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by_kajur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'teacher_id'], 'student_teacher_unique');
            $table->index(['teacher_id', 'student_id'], 'teacher_student_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_school_supervisors');
    }
};

