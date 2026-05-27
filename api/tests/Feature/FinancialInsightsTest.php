<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FinancialInsightsTest extends TestCase
{
    public function test_demo_endpoint_returns_south_african_finance_insights(): void
    {
        $response = $this->getJson('/api/demo');

        $response
            ->assertOk()
            ->assertJsonPath('profile.currency', 'ZAR')
            ->assertJsonStructure([
                'metrics',
                'healthScore',
                'categories',
                'recurring',
                'goals',
                'insights',
                'transactions',
            ]);
    }

    public function test_statement_preview_accepts_csv_and_detects_categories(): void
    {
        $csv = implode("\n", [
            'Date,Description,Amount',
            '2026-05-25,Salary Deposit,18000',
            '2026-05-26,Checkers Groceries,-850',
            '2026-05-27,Netflix Subscription,-199',
            '2026-05-28,Vodacom Contract Debit Order,-399',
        ]);

        $file = UploadedFile::fake()->createWithContent('statement.csv', $csv);

        $response = $this->postJson('/api/statements/preview', [
            'bank' => 'Capitec',
            'statement' => $file,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('profile.bank', 'Capitec')
            ->assertJsonFragment(['name' => 'Groceries'])
            ->assertJsonFragment(['merchant' => 'Vodacom Contract Debit']);
    }
}
