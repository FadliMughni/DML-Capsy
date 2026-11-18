<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Satu baris konten company profile yang dikelola Marketing. */
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('company_name', 150);
            $table->string('brand_name', 100)->nullable();
            $table->string('tagline', 200)->nullable();
            $table->string('founded_info', 200)->nullable();
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();      // satu poin per baris
            $table->string('address', 300)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('fax', 50)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('operating_hours')->nullable(); // satu baris per jadwal
            $table->string('maps_url', 500)->nullable();
            $table->json('stats')->nullable();           // [{value, label}]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
