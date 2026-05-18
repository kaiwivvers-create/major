<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('major_id')->constrained('majors')->cascadeOnDelete();
            $table->string('name');
            $table->string('academic_year', 20)->nullable();
            $table->timestamps();

            $table->index(['major_id', 'name']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->date('attendance_date');
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('status', ['pending', 'present', 'late', 'rejected'])->default('pending');
            $table->timestamps();

            $table->index(['student_id', 'attendance_date']);
        });

        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kajur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->date('work_date');
            $table->string('title');
            $table->text('description');
            $table->string('evidence_path')->nullable();
            $table->text('kajur_feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'work_date']);
        });

        Schema::create('weekly_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('bindo_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->text('learning_notes');
            $table->text('missing_info_notes')->nullable();
            $table->enum('status', ['draft', 'submitted', 'needs_revision', 'approved'])->default('draft');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'week_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_journals');
        Schema::dropIfExists('daily_logs');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('classes');
        Schema::dropIfExists('majors');
    }
};
