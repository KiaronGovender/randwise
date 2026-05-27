<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('statement_import_id')->constrained()->cascadeOnDelete();
            $table->date('occurred_on');
            $table->string('description');
            $table->string('merchant');
            $table->decimal('amount', 12, 2);
            $table->string('category');
            $table->string('type', 20);
            $table->boolean('is_recurring_candidate')->default(false);
            $table->timestamps();

            $table->index(['financial_profile_id', 'occurred_on']);
            $table->index(['statement_import_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
