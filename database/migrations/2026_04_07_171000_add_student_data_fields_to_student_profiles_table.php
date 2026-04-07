<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('major_name', 120)->nullable()->after('birth_date');
            $table->string('pkl_place_name', 150)->nullable()->after('address');
            $table->text('pkl_place_address')->nullable()->after('pkl_place_name');
            $table->string('pkl_place_phone', 30)->nullable()->after('pkl_place_address');
            $table->date('pkl_start_date')->nullable()->after('pkl_place_phone');
            $table->date('pkl_end_date')->nullable()->after('pkl_start_date');
            $table->string('mentor_teacher_name', 150)->nullable()->after('pkl_end_date');
            $table->string('school_supervisor_teacher_name', 150)->nullable()->after('mentor_teacher_name');
            $table->string('company_instructor_position', 150)->nullable()->after('school_supervisor_teacher_name');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'major_name',
                'pkl_place_name',
                'pkl_place_address',
                'pkl_place_phone',
                'pkl_start_date',
                'pkl_end_date',
                'mentor_teacher_name',
                'school_supervisor_teacher_name',
                'company_instructor_position',
            ]);
        });
    }
};
