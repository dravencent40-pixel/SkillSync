<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->decimal('clean_code_score', 5, 2)->nullable();
            $table->decimal('security_score', 5, 2)->nullable();
            $table->decimal('efficiency_score', 5, 2)->nullable();
            $table->decimal('overall_score', 5, 2)->nullable();
            $table->text('summary')->nullable();
            $table->json('findings_json')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_reviews');
    }
};
