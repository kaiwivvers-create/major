<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('app_brandings')) {
            Schema::create('app_brandings', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::table('app_brandings', function (Blueprint $table) {
            if (! Schema::hasColumn('app_brandings', 'app_name')) {
                $table->string('app_name', 120)->default('KIPS');
            }

            if (! Schema::hasColumn('app_brandings', 'short_name')) {
                $table->string('short_name', 10)->default('K');
            }

            if (! Schema::hasColumn('app_brandings', 'logo_path')) {
                $table->string('logo_path')->nullable();
            }

            if (! Schema::hasColumn('app_brandings', 'hero_kicker')) {
                $table->string('hero_kicker', 255)->default('Web-Based Internship Monitoring and Attendance Validation Information System');
            }

            if (! Schema::hasColumn('app_brandings', 'hero_heading')) {
                $table->string('hero_heading', 255)->default('Seamlessly Bridging Education and Industry.');
            }

            if (! Schema::hasColumn('app_brandings', 'hero_body')) {
                $table->text('hero_body')->nullable();
            }

            if (! Schema::hasColumn('app_brandings', 'hero_image_path')) {
                $table->string('hero_image_path')->nullable();
            }

            if (! Schema::hasColumn('app_brandings', 'login_heading')) {
                $table->string('login_heading', 255)->default('Log in to your account');
            }

            if (! Schema::hasColumn('app_brandings', 'login_subheading')) {
                $table->string('login_subheading', 255)->default('Use your registered account to access the dashboard.');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('app_brandings')) {
            return;
        }

        Schema::table('app_brandings', function (Blueprint $table) {
            $columns = [
                'short_name',
                'hero_kicker',
                'hero_heading',
                'hero_body',
                'hero_image_path',
                'login_heading',
                'login_subheading',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('app_brandings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
