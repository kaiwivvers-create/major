<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_companies', function (Blueprint $table) {
            if (!Schema::hasColumn('partner_companies', 'office_latitude')) {
                $table->decimal('office_latitude', 10, 7)->nullable()->after('max_students');
            }
            if (!Schema::hasColumn('partner_companies', 'office_longitude')) {
                $table->decimal('office_longitude', 10, 7)->nullable()->after('office_latitude');
            }
            if (!Schema::hasColumn('partner_companies', 'geofence_radius_meters')) {
                $table->unsignedInteger('geofence_radius_meters')->nullable()->after('office_longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('partner_companies', function (Blueprint $table) {
            $dropColumns = [];
            if (Schema::hasColumn('partner_companies', 'geofence_radius_meters')) {
                $dropColumns[] = 'geofence_radius_meters';
            }
            if (Schema::hasColumn('partner_companies', 'office_longitude')) {
                $dropColumns[] = 'office_longitude';
            }
            if (Schema::hasColumn('partner_companies', 'office_latitude')) {
                $dropColumns[] = 'office_latitude';
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
