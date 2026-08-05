<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 50)->default('draft'); // draft, active, archived
            $table->json('schema')->nullable(); // JSON schema of the form fields
            $table->unsignedInteger('views_count')->default(0);
            $table->string('share_token', 64)->unique();
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('share_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
