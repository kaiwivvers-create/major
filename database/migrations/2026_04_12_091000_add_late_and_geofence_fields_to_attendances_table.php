<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'late_minutes')) {
                $table->unsignedInteger('late_minutes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('attendances', 'geofence_distance_meters')) {
                $table->decimal('geofence_distance_meters', 10, 2)->nullable()->after('late_minutes');
            }
            if (!Schema::hasColumn('attendances', 'geofence_radius_meters')) {
                $table->unsignedInteger('geofence_radius_meters')->nullable()->after('geofence_distance_meters');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'geofence_radius_meters')) {
                $table->dropColumn('geofence_radius_meters');
            }
            if (Schema::hasColumn('attendances', 'geofence_distance_meters')) {
                $table->dropColumn('geofence_distance_meters');
            }
            if (Schema::hasColumn('attendances', 'late_minutes')) {
                $table->dropColumn('late_minutes');
            }
        });
    }
};

