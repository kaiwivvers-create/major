<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_logs', 'mentor_review_status')) {
                $table->enum('mentor_review_status', ['pending', 'approved', 'revise'])
                    ->default('pending')
                    ->after('scored_at');
            }
            if (!Schema::hasColumn('daily_logs', 'mentor_revision_notes')) {
                $table->text('mentor_revision_notes')->nullable()->after('mentor_review_status');
            }
            if (!Schema::hasColumn('daily_logs', 'mentor_reviewed_at')) {
                $table->timestamp('mentor_reviewed_at')->nullable()->after('mentor_revision_notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_logs', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('daily_logs', 'mentor_reviewed_at')) {
                $dropColumns[] = 'mentor_reviewed_at';
            }
            if (Schema::hasColumn('daily_logs', 'mentor_revision_notes')) {
                $dropColumns[] = 'mentor_revision_notes';
            }
            if (Schema::hasColumn('daily_logs', 'mentor_review_status')) {
                $dropColumns[] = 'mentor_review_status';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
