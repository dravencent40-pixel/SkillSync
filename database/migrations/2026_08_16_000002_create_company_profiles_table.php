<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('company_name')->nullable();
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->text('about')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
