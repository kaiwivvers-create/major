<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('address')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('website_url', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['name', 'address'], 'partner_companies_name_address_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_companies');
    }
};
