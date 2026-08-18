<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->timestamps();
        });

        Schema::table('mentor_conversations', function (Blueprint $table) {
            $table->timestamps();
        });

        Schema::table('mentor_messages', function (Blueprint $table) {
            $table->timestamps();
        });

        Schema::table('recommendations', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropTimestamps();
        });

        Schema::table('mentor_conversations', function (Blueprint $table) {
            $table->dropTimestamps();
        });

        Schema::table('mentor_messages', function (Blueprint $table) {
            $table->dropTimestamps();
        });

        Schema::table('recommendations', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
