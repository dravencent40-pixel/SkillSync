<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_profile_tracks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->decimal('criterion1_score', 5, 2)->default(0);
            $table->decimal('criterion2_score', 5, 2)->default(0);
            $table->decimal('criterion3_score', 5, 2)->default(0);
            $table->decimal('comprehension_avg', 5, 2)->default(0);
            $table->unsignedInteger('tasks_completed')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_profile_tracks');
    }
};
