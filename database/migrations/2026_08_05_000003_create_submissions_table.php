<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_version_id')->nullable()->constrained('form_versions')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable(); // performance analytics
            $table->timestamps();

            // Indexes
            $table->index('form_id');
            $table->index('form_version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
