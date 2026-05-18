<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('app_brandings', function (Blueprint $table) {
            $table->string('primary_color', 7)->default('#4f46e5')->after('login_subheading');
            $table->string('secondary_color', 7)->default('#10b981')->after('primary_color');
            $table->string('accent_color', 7)->default('#f59e0b')->after('secondary_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_brandings', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'secondary_color', 'accent_color']);
        });
    }
};
