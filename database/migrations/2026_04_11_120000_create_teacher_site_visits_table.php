<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_site_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name', 150)->nullable();
            $table->text('company_address')->nullable();
            $table->string('photo_path');
            $table->text('visit_notes')->nullable();
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->index(['teacher_id', 'visited_at']);
            $table->index(['student_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_site_visits');
    }
};
