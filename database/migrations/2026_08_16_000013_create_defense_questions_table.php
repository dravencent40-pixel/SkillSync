<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('defense_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedInteger('order_index')->default(1);
            $table->text('question');
            $table->text('answer')->nullable();
            $table->decimal('answer_score', 5, 2)->nullable();
            $table->text('answer_feedback')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('defense_questions');
    }
};
