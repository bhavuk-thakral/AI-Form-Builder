<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->string('field_key', 100);
            $table->text('answer_value')->nullable(); // stores user response value
            $table->timestamps();

            // Indexes
            $table->index('submission_id');
            $table->index('field_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_answers');
    }
};
