<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('birth_place', 120)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('parent_name', 150)->nullable();
            $table->string('parent_phone', 30)->nullable();
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->text('company_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
