<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('statement_import_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('target_amount', 12, 2);
            $table->decimal('saved_amount', 12, 2)->default(0);
            $table->date('deadline')->nullable();
            $table->decimal('recommended_monthly', 12, 2)->default(0);
            $table->string('status')->default('Draft');
            $table->timestamps();

            $table->index(['financial_profile_id', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_goals');
    }
};
