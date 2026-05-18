<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'mentor_company_name')) {
                $table->string('mentor_company_name', 150)
                    ->nullable()
                    ->after('teacher_class_name')
                    ->comment('Company name for mentor role');
            }
            if (!Schema::hasColumn('users', 'partner_company_id')) {
                $table->foreignId('partner_company_id')
                    ->nullable()
                    ->after('mentor_company_name')
                    ->constrained('partner_companies')
                    ->nullOnDelete()
                    ->comment('Reference to partner company for mentor assignment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'partner_company_id')) {
                $table->dropForeignKeyIfExists(['partner_company_id']);
                $table->dropColumn('partner_company_id');
            }
            if (Schema::hasColumn('users', 'mentor_company_name')) {
                $table->dropColumn('mentor_company_name');
            }
        });
    }
};
