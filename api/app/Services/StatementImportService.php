<?php

namespace App\Services;

use App\Models\FinancialGoal;
use App\Models\FinancialProfile;
use App\Models\StatementImport;
use App\Models\Transaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class StatementImportService
{
    public function __construct(
        private readonly StatementAnalyzer $analyzer,
        private readonly StatementImportPresenter $presenter,
    ) {}

    public function store(UploadedFile $file, string $bank): StatementImport
    {
        $rawTransactions = $this->analyzer->parseUploadedFile($file);

        return $this->storeRawTransactions($rawTransactions, $bank, $file->getClientOriginalName());
    }

    public function storeFromContents(string $contents, string $sourceFilename, string $bank): StatementImport
    {
        $rawTransactions = $this->analyzer->parseCsvText($contents);

        return $this->storeRawTransactions($rawTransactions, $bank, $sourceFilename);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawTransactions
     */
    private function storeRawTransactions(array $rawTransactions, string $bank, string $sourceFilename): StatementImport
    {
        $preparedTransactions = $this->analyzer->prepareTransactions($rawTransactions);
        $analysis = $this->analyzer->analyze($rawTransactions, $bank);
        $metricValues = collect($analysis['metrics'])->keyBy('label');
        $recurringMerchants = collect($analysis['recurring'])->pluck('merchant')->all();

        return DB::transaction(function () use ($sourceFilename, $bank, $analysis, $preparedTransactions, $metricValues, $recurringMerchants) {
            $profile = FinancialProfile::query()->create([
                'display_name' => 'RandWise profile',
                'bank_name' => $bank,
                'currency' => 'ZAR',
                'privacy_mode' => true,
            ]);

            $import = StatementImport::query()->create([
                'financial_profile_id' => $profile->id,
                'source_filename' => $sourceFilename,
                'bank_name' => $bank,
                'status' => 'completed',
                'imported_at' => now(),
                'transaction_count' => $preparedTransactions->count(),
                'total_income' => $metricValues->get('Monthly income')['value'] ?? 0,
                'total_spending' => $metricValues->get('Monthly spending')['value'] ?? 0,
                'net_cash_flow' => $metricValues->get('Net cash flow')['value'] ?? 0,
                'savings_rate' => $metricValues->get('Savings rate')['value'] ?? 0,
                'analysis_snapshot' => $analysis,
            ]);

            $preparedTransactions->each(function (array $transaction) use ($profile, $import, $recurringMerchants): void {
                Transaction::query()->create([
                    'financial_profile_id' => $profile->id,
                    'statement_import_id' => $import->id,
                    'occurred_on' => $transaction['date'],
                    'description' => $transaction['description'],
                    'merchant' => $transaction['merchant'],
                    'amount' => $transaction['amount'],
                    'category' => $transaction['category'],
                    'type' => $transaction['type'],
                    'is_recurring_candidate' => in_array($transaction['merchant'], $recurringMerchants, true),
                ]);
            });

            collect($analysis['goals'])->each(function (array $goal) use ($profile, $import): void {
                FinancialGoal::query()->create([
                    'financial_profile_id' => $profile->id,
                    'statement_import_id' => $import->id,
                    'name' => $goal['name'],
                    'target_amount' => $goal['target'],
                    'saved_amount' => $goal['saved'],
                    'deadline' => $goal['deadline'],
                    'recommended_monthly' => $goal['recommendedMonthly'],
                    'status' => $goal['status'],
                ]);
            });

            return $import->load(['profile', 'transactions', 'goals']);
        });
    }

    public function dashboard(StatementImport $import): array
    {
        return $this->presenter->dashboard($import);
    }
}
