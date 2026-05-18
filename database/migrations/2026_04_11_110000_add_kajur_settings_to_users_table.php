<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'kajur_major_name')) {
                $table->string('kajur_major_name', 120)->nullable()->after('permissions_json');
            }
            if (!Schema::hasColumn('users', 'kajur_red_flag_days')) {
                $table->unsignedTinyInteger('kajur_red_flag_days')->nullable()->after('kajur_major_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('users', 'kajur_red_flag_days')) {
                $dropColumns[] = 'kajur_red_flag_days';
            }
            if (Schema::hasColumn('users', 'kajur_major_name')) {
                $dropColumns[] = 'kajur_major_name';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
