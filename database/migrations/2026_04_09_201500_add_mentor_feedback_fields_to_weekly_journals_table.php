<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_journals', function (Blueprint $table) {
            if (!Schema::hasColumn('weekly_journals', 'mentor_feedback_summary')) {
                $table->text('mentor_feedback_summary')->nullable()->after('missing_info_notes');
            }
            if (!Schema::hasColumn('weekly_journals', 'mentor_attitude_rating')) {
                $table->unsignedTinyInteger('mentor_attitude_rating')->nullable()->after('mentor_feedback_summary');
            }
            if (!Schema::hasColumn('weekly_journals', 'mentor_skill_rating')) {
                $table->unsignedTinyInteger('mentor_skill_rating')->nullable()->after('mentor_attitude_rating');
            }
        });
    }

    public function down(): void
    {
        Schema::table('weekly_journals', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('weekly_journals', 'mentor_skill_rating')) {
                $dropColumns[] = 'mentor_skill_rating';
            }
            if (Schema::hasColumn('weekly_journals', 'mentor_attitude_rating')) {
                $dropColumns[] = 'mentor_attitude_rating';
            }
            if (Schema::hasColumn('weekly_journals', 'mentor_feedback_summary')) {
                $dropColumns[] = 'mentor_feedback_summary';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
