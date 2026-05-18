<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teacher_red_flag_interventions')) {
            return;
        }

        Schema::create('teacher_red_flag_interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('risk_score')->default(0);
            $table->string('risk_level', 20)->default('low');
            $table->enum('intervention_status', ['open', 'monitoring', 'resolved'])->default('open');
            $table->text('intervention_notes');
            $table->date('follow_up_date')->nullable();
            $table->json('indicator_snapshot')->nullable();
            $table->timestamp('actioned_at');
            $table->timestamps();

            $table->index(['teacher_id', 'student_id'], 'tri_teacher_student_idx');
            $table->index(['teacher_id', 'intervention_status'], 'tri_teacher_status_idx');
            $table->index(['teacher_id', 'actioned_at'], 'tri_teacher_actioned_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_red_flag_interventions');
    }
};
