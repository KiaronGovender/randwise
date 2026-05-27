<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('display_name')->default('RandWise profile');
            $table->string('bank_name')->default('Unknown bank');
            $table->string('currency', 3)->default('ZAR');
            $table->boolean('privacy_mode')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_profiles');
    }
};
