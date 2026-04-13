<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_alert_notifications', function (Blueprint $table) {
            $table->id();
            $table->date('alert_date');
            $table->string('recipient_role', 30);
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('alert_type', 60);
            $table->string('major_name', 30)->nullable();
            $table->string('class_name', 120)->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['alert_date', 'recipient_role']);
            $table->unique(
                ['alert_date', 'recipient_role', 'recipient_user_id', 'alert_type', 'major_name', 'class_name'],
                'attendance_alert_notifications_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_alert_notifications');
    }
};

