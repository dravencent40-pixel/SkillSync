<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->string('status')->default('pending');
            $table->decimal('comprehension_score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->boolean('ai_assisted')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('evaluated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_sessions');
    }
};
