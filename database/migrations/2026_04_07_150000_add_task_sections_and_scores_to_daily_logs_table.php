<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->text('planned_today')->nullable()->after('description');
            $table->text('work_realization')->nullable()->after('planned_today');
            $table->text('assigned_work')->nullable()->after('work_realization');
            $table->text('field_problems')->nullable()->after('assigned_work');
            $table->text('notes')->nullable()->after('field_problems');

            $table->unsignedTinyInteger('score_smile')->nullable()->after('notes');
            $table->unsignedTinyInteger('score_friendliness')->nullable()->after('score_smile');
            $table->unsignedTinyInteger('score_appearance')->nullable()->after('score_friendliness');
            $table->unsignedTinyInteger('score_communication')->nullable()->after('score_appearance');
            $table->unsignedTinyInteger('score_work_realization')->nullable()->after('score_communication');
            $table->foreignId('score_mentor_id')->nullable()->after('score_work_realization')->constrained('users')->nullOnDelete();
            $table->timestamp('scored_at')->nullable()->after('score_mentor_id');
        });
    }

    public function down(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('score_mentor_id');
            $table->dropColumn([
                'planned_today',
                'work_realization',
                'assigned_work',
                'field_problems',
                'notes',
                'score_smile',
                'score_friendliness',
                'score_appearance',
                'score_communication',
                'score_work_realization',
                'scored_at',
            ]);
        });
    }
};
