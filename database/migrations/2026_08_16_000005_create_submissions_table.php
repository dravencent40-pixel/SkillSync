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
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->string('language')->default('php');
            $table->longText('code_content');
            $table->string('file_path')->nullable();
            $table->string('external_link')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamp('submitted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
