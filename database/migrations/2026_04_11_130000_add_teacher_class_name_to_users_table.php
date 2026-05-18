<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'teacher_class_name')) {
                $table->string('teacher_class_name', 120)
                    ->nullable()
                    ->after('kajur_red_flag_days');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'teacher_class_name')) {
                $table->dropColumn('teacher_class_name');
            }
        });
    }
};
