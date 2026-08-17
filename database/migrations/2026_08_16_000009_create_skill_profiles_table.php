<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('overall_score', 5, 2)->default(0);
            $table->decimal('clean_code_avg', 5, 2)->default(0);
            $table->decimal('security_avg', 5, 2)->default(0);
            $table->decimal('efficiency_avg', 5, 2)->default(0);
            $table->decimal('comprehension_avg', 5, 2)->default(0);
            $table->unsignedInteger('tasks_completed')->default(0);
            $table->string('badge')->nullable();
            $table->text('strengths')->nullable();
            $table->text('weaknesses')->nullable();
            $table->longText('narrative')->nullable();
            $table->boolean('is_public')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_profiles');
    }
};
