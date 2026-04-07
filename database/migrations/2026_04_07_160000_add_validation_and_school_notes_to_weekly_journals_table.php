<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_journals', function (Blueprint $table) {
            $table->foreignId('kajur_id')->nullable()->after('mentor_id')->constrained('users')->nullOnDelete();
            $table->text('student_mentor_notes')->nullable()->after('learning_notes');
            $table->boolean('mentor_is_correct')->nullable()->after('student_mentor_notes');
            $table->text('kajur_notes')->nullable()->after('missing_info_notes');
            $table->text('bindo_notes')->nullable()->after('kajur_notes');
            $table->timestamp('mentor_reviewed_at')->nullable()->after('reviewed_at');
            $table->timestamp('kajur_reviewed_at')->nullable()->after('mentor_reviewed_at');
            $table->timestamp('bindo_reviewed_at')->nullable()->after('kajur_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_journals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('kajur_id');
            $table->dropColumn([
                'student_mentor_notes',
                'mentor_is_correct',
                'kajur_notes',
                'bindo_notes',
                'mentor_reviewed_at',
                'kajur_reviewed_at',
                'bindo_reviewed_at',
            ]);
        });
    }
};
