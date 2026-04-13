<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance_calendar_exceptions')) {
            Schema::create('attendance_calendar_exceptions', function (Blueprint $table) {
                $table->id();
                $table->date('exception_date');
                $table->enum('exception_type', ['holiday', 'school_off', 'company_off']);
                $table->string('major_name', 30)->nullable();
                $table->string('class_name', 120)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        Schema::table('attendance_calendar_exceptions', function (Blueprint $table) {
            if (! Schema::hasIndex('attendance_calendar_exceptions', 'attendance_calendar_lookup_idx')) {
                $table->index(
                    ['exception_date', 'major_name', 'class_name'],
                    'attendance_calendar_lookup_idx'
                );
            }

            if (! Schema::hasIndex('attendance_calendar_exceptions', 'attendance_calendar_unique', 'unique')) {
                $table->unique(
                    ['exception_date', 'exception_type', 'major_name', 'class_name'],
                    'attendance_calendar_unique'
                );
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_calendar_exceptions');
    }
};
