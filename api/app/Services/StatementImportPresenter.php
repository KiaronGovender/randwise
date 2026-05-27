<?php

namespace App\Services;

use App\Models\StatementImport;

class StatementImportPresenter
{
    public function dashboard(StatementImport $import): array
    {
        $import->loadMissing(['profile', 'transactions', 'goals']);

        $dashboard = $import->analysis_snapshot;
        $firstTransaction = $import->transactions->sortBy('occurred_on')->first();
        $lastTransaction = $import->transactions->sortByDesc('occurred_on')->first();

        $dashboard['profile'] = [
            'name' => $import->profile->display_name,
            'bank' => $import->bank_name,
            'period' => $firstTransaction && $lastTransaction
                ? $firstTransaction->occurred_on->format('d M Y').' - '.$lastTransaction->occurred_on->format('d M Y')
                : 'No transactions yet',
            'currency' => $import->profile->currency,
            'privacyMode' => $import->profile->privacy_mode,
        ];

        $dashboard['transactions'] = $import->transactions
            ->sortByDesc('occurred_on')
            ->take(14)
            ->values()
            ->map(fn ($transaction) => [
                'date' => $transaction->occurred_on->toDateString(),
                'description' => $transaction->description,
                'merchant' => $transaction->merchant,
                'amount' => $transaction->amount,
                'category' => $transaction->category,
                'type' => $transaction->type,
            ])
            ->all();

        $dashboard['goals'] = $import->goals
            ->map(fn ($goal) => [
                'name' => $goal->name,
                'target' => $goal->target_amount,
                'saved' => $goal->saved_amount,
                'deadline' => $goal->deadline?->toDateString(),
                'recommendedMonthly' => $goal->recommended_monthly,
                'status' => $goal->status,
            ])
            ->all();

        $dashboard['meta']['importId'] = $import->id;
        $dashboard['meta']['sourceFilename'] = $import->source_filename;
        $dashboard['meta']['transactionCount'] = $import->transaction_count;
        $dashboard['meta']['persisted'] = true;

        return $dashboard;
    }

    public function summary(StatementImport $import): array
    {
        return [
            'id' => $import->id,
            'bank' => $import->bank_name,
            'sourceFilename' => $import->source_filename,
            'importedAt' => $import->imported_at->toIso8601String(),
            'transactionCount' => $import->transaction_count,
            'netCashFlow' => $import->net_cash_flow,
            'savingsRate' => $import->savings_rate,
        ];
    }
}
