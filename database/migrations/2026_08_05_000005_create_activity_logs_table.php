<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('form_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100); // e.g., created, updated, deleted, submitted, ai_generated
            $table->text('description');
            $table->json('metadata')->nullable(); // holds diagnostic payload
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('form_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
