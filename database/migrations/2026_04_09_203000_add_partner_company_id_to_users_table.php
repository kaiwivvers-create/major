<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'partner_company_id')) {
                $table->foreignId('partner_company_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('partner_companies')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'partner_company_id')) {
                $table->dropConstrainedForeignId('partner_company_id');
            }
        });
    }
};
