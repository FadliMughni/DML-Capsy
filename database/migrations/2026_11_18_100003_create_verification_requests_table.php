<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();

            // Pengajuan oleh Marketing (PBI 16)
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('requested_at');
            $table->text('request_notes')->nullable();

            // Hasil verifikasi oleh Gudang (PBI 17)
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('result', 30)->nullable(); // App\Enums\VerificationResult
            $table->unsignedInteger('available_quantity')->nullable();
            $table->date('estimated_date')->nullable();
            $table->string('estimated_duration', 100)->nullable();
            $table->text('verification_notes')->nullable();

            $table->timestamps();

            $table->index(['verified_at', 'requested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
};
