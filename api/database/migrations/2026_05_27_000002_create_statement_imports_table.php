<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_profile_id')->constrained()->cascadeOnDelete();
            $table->string('source_filename');
            $table->string('bank_name');
            $table->string('status')->default('completed');
            $table->timestamp('imported_at');
            $table->unsignedInteger('transaction_count')->default(0);
            $table->decimal('total_income', 12, 2)->default(0);
            $table->decimal('total_spending', 12, 2)->default(0);
            $table->decimal('net_cash_flow', 12, 2)->default(0);
            $table->decimal('savings_rate', 6, 2)->default(0);
            $table->json('analysis_snapshot');
            $table->timestamps();

            $table->index(['financial_profile_id', 'imported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_imports');
    }
};
