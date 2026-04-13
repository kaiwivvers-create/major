<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('implementation_timeline_statuses', function (Blueprint $table) {
            $table->id();
            $table->date('week_start')->unique();
            $table->date('week_end');
            $table->string('status_label', 120);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('implementation_timeline_statuses');
    }
};

