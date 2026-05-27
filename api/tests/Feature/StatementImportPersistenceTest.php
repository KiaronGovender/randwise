<?php

namespace Tests\Feature;

use App\Models\StatementImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StatementImportPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_statement_is_persisted_with_transactions_and_goals(): void
    {
        $file = UploadedFile::fake()->createWithContent('capitec.csv', implode("\n", [
            'Date,Description,Amount',
            '2026-04-25,Salary Deposit,18000',
            '2026-04-01,Rent Greenpoint Property,-6500',
            '2026-04-06,Vodacom Contract Debit Order,-399',
            '2026-04-26,Checkers Groceries,-950',
            '2026-05-25,Salary Deposit,18000',
            '2026-05-01,Rent Greenpoint Property,-6500',
            '2026-05-06,Vodacom Contract Debit Order,-399',
            '2026-05-26,Checkers Groceries,-850',
        ]));

        $response = $this->postJson('/api/imports', [
            'bank' => 'Capitec',
            'statement' => $file,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('profile.bank', 'Capitec')
            ->assertJsonPath('meta.persisted', true)
            ->assertJsonPath('meta.transactionCount', 8);

        $this->assertDatabaseCount('financial_profiles', 1);
        $this->assertDatabaseCount('statement_imports', 1);
        $this->assertDatabaseCount('transactions', 8);
        $this->assertDatabaseHas('transactions', [
            'merchant' => 'Vodacom Contract Debit',
            'category' => 'Airtime/Data',
            'is_recurring_candidate' => true,
        ]);
        $this->assertDatabaseHas('financial_goals', [
            'name' => 'Emergency fund',
        ]);
    }

    public function test_import_history_and_show_endpoint_return_saved_data(): void
    {
        $file = UploadedFile::fake()->createWithContent('statement.csv', implode("\n", [
            'Date,Description,Amount',
            '2026-05-25,Salary Deposit,12000',
            '2026-05-26,Shoprite Groceries,-500',
        ]));

        $this->postJson('/api/imports', [
            'bank' => 'FNB',
            'statement' => $file,
        ])->assertCreated();

        $import = StatementImport::query()->firstOrFail();

        $this->getJson('/api/imports')
            ->assertOk()
            ->assertJsonPath('data.0.id', $import->id)
            ->assertJsonPath('data.0.bank', 'FNB');

        $this->getJson("/api/imports/{$import->id}")
            ->assertOk()
            ->assertJsonPath('profile.bank', 'FNB')
            ->assertJsonPath('meta.sourceFilename', 'statement.csv');
    }

    public function test_statement_can_be_imported_from_csv_text_payload(): void
    {
        $csv = implode("\n", [
            'Date,Description,Amount',
            '2026-05-25,Salary Deposit,12000',
            '2026-05-26,Shoprite Groceries,-500',
        ]);

        $response = $this->postJson('/api/imports', [
            'bank' => 'Nedbank',
            'sourceFilename' => 'nedbank.csv',
            'statementContents' => $csv,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('profile.bank', 'Nedbank')
            ->assertJsonPath('meta.sourceFilename', 'nedbank.csv')
            ->assertJsonPath('meta.transactionCount', 2);

        $this->assertDatabaseHas('statement_imports', [
            'source_filename' => 'nedbank.csv',
            'transaction_count' => 2,
        ]);
    }
}
