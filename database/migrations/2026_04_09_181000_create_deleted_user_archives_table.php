<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deleted_user_archives', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deleted_user_id');
            $table->foreignId('deleted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('user_data')->nullable();
            $table->json('profile_data')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();

            $table->index(['deleted_user_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deleted_user_archives');
    }
};
