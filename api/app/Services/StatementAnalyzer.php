<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StatementAnalyzer
{
    private const CATEGORY_COLORS = [
        'Groceries' => '#2f855a',
        'Rent' => '#334155',
        'Transport/Fuel' => '#2563eb',
        'Airtime/Data' => '#7c3aed',
        'Prepaid Electricity' => '#d97706',
        'Insurance' => '#0891b2',
        'Debit Orders' => '#be123c',
        'Takeaways' => '#ea580c',
        'Shopping' => '#db2777',
        'Banking Fees' => '#64748b',
        'Entertainment' => '#9333ea',
        'Health' => '#16a34a',
        'Income' => '#15803d',
        'Other' => '#475569',
    ];

    public function demo(): array
    {
        return $this->analyze($this->sampleTransactions(), 'Demo Capitec CSV');
    }

    public function fromUploadedFile(UploadedFile $file, string $bank): array
    {
        $transactions = $this->parseUploadedFile($file);

        return $this->analyze($transactions, $bank);
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     */
    public function analyze(array $transactions, string $bank): array
    {
        $prepared = $this->prepareTransactions($transactions);

        $income = round($prepared->where('amount', '>', 0)->sum('amount'), 2);
        $spending = round(abs($prepared->where('amount', '<', 0)->sum('amount')), 2);

        $categories = $this->categoryBreakdown($prepared, $spending);
        $recurring = $this->recurringPayments($prepared);
        $trends = $this->monthlyTrends($prepared);
        $monthlyIncome = round((float) collect($trends)->avg('income'), 2);
        $monthlySpending = round((float) collect($trends)->avg('spending'), 2);
        $netCashFlow = round($monthlyIncome - $monthlySpending, 2);
        $savingsRate = $monthlyIncome > 0 ? round(($netCashFlow / $monthlyIncome) * 100, 1) : 0;
        $opportunity = $this->savingsOpportunity($categories);

        return [
            'profile' => [
                'name' => 'RandWise demo profile',
                'bank' => $bank,
                'period' => $this->periodLabel($prepared),
                'currency' => 'ZAR',
                'privacyMode' => true,
            ],
            'metrics' => [
                [
                    'label' => 'Monthly income',
                    'value' => $monthlyIncome,
                    'format' => 'currency',
                    'tone' => 'positive',
                    'detail' => 'Average across imported deposits',
                ],
                [
                    'label' => 'Monthly spending',
                    'value' => $monthlySpending,
                    'format' => 'currency',
                    'tone' => 'warning',
                    'detail' => 'Average card swipes, EFTs, debit orders',
                ],
                [
                    'label' => 'Net cash flow',
                    'value' => $netCashFlow,
                    'format' => 'currency',
                    'tone' => $netCashFlow >= 0 ? 'positive' : 'danger',
                    'detail' => $netCashFlow >= 0 ? 'Money left after expenses' : 'Shortfall to fix next month',
                ],
                [
                    'label' => 'Savings rate',
                    'value' => $savingsRate,
                    'format' => 'percent',
                    'tone' => $savingsRate >= 15 ? 'positive' : 'warning',
                    'detail' => 'Target: 15% or better',
                ],
            ],
            'healthScore' => $this->healthScore($savingsRate, $recurring, $opportunity),
            'cashLeftUntilPayday' => max($netCashFlow, 0),
            'paydayCountdown' => 9,
            'categories' => $categories,
            'trends' => $trends,
            'recurring' => $recurring,
            'goals' => $this->goals(max($this->averageMonthlyNet($trends), 0)),
            'insights' => $this->insights($categories, $recurring, $netCashFlow, $opportunity),
            'transactions' => $prepared
                ->sortByDesc('date')
                ->take(14)
                ->values()
                ->all(),
            'meta' => [
                'transactionCount' => $prepared->count(),
                'savingsOpportunity' => $opportunity,
                'generatedAt' => now()->toIso8601String(),
            ],
        ];
    }

    public function parseUploadedFile(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [];
        }

        return $this->parseCsvHandle($handle);
    }

    public function parseCsvText(string $contents): array
    {
        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return [];
        }

        fwrite($handle, $contents);
        rewind($handle);

        return $this->parseCsvHandle($handle);
    }

    /**
     * @param  resource  $handle
     */
    private function parseCsvHandle($handle): array
    {
        $headers = null;
        $transactions = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isBlankRow($row)) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(fn (string $value) => $this->normalizeKey($value), $row);

                continue;
            }

            $row = array_pad($row, count($headers), null);
            $mapped = array_combine($headers, array_slice($row, 0, count($headers)));

            if ($mapped === false) {
                continue;
            }

            $transaction = $this->mapCsvTransaction($mapped);

            if ($transaction !== null) {
                $transactions[] = $transaction;
            }
        }

        fclose($handle);

        return $transactions;
    }

    /**
     * @param  array<int, array<string, mixed>>  $transactions
     * @return Collection<int, array<string, mixed>>
     */
    public function prepareTransactions(array $transactions): Collection
    {
        return collect($transactions)
            ->map(fn (array $transaction) => $this->prepareTransaction($transaction))
            ->filter(fn (?array $transaction) => $transaction !== null)
            ->sortBy('date')
            ->values();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function mapCsvTransaction(array $row): ?array
    {
        $amount = $this->readAmount($row, ['amount', 'value', 'transactionamount']);

        if ($amount === null) {
            $debit = $this->readAmount($row, ['debit', 'withdrawal', 'withdrawals', 'paidout', 'moneyout']);
            $credit = $this->readAmount($row, ['credit', 'deposit', 'deposits', 'paidin', 'moneyin']);
            $amount = round(($credit ?? 0) - ($debit ?? 0), 2);
        }

        if ($amount === 0.0) {
            return null;
        }

        return [
            'date' => $this->readValue($row, ['date', 'transactiondate', 'postingdate', 'valuedate']) ?? now()->toDateString(),
            'description' => $this->readValue($row, ['description', 'details', 'narrative', 'reference', 'merchant']) ?? 'Imported transaction',
            'amount' => $amount,
        ];
    }

    /**
     * @param  array<string, mixed>  $transaction
     */
    private function prepareTransaction(array $transaction): ?array
    {
        $amount = round((float) ($transaction['amount'] ?? 0), 2);

        if ($amount === 0.0) {
            return null;
        }

        $description = trim((string) ($transaction['description'] ?? 'Imported transaction'));
        $date = $this->parseDate((string) ($transaction['date'] ?? now()->toDateString()));
        $category = $amount > 0 ? 'Income' : $this->categorize($description);

        return [
            'date' => $date->toDateString(),
            'description' => $description,
            'merchant' => $this->merchantName($description),
            'amount' => $amount,
            'category' => $category,
            'type' => $amount > 0 ? 'income' : 'expense',
        ];
    }

    private function categorize(string $description): string
    {
        $rules = [
            'Groceries' => ['checkers', 'shoprite', 'pick n pay', 'pnp', 'woolworths', 'spar', 'boxer', 'food lovers'],
            'Rent' => ['rent', 'rental', 'landlord', 'property'],
            'Transport/Fuel' => ['engen', 'shell', 'bp', 'sasol', 'total', 'caltex', 'uber', 'bolt', 'gautrain', 'fuel'],
            'Airtime/Data' => ['vodacom', 'mtn', 'telkom', 'cell c', 'rain', 'airtime', 'data bundle'],
            'Prepaid Electricity' => ['electricity', 'eskom', 'prepaid power', 'municipality'],
            'Insurance' => ['insurance', 'discovery', 'outsurance', 'old mutual', 'sanlam'],
            'Debit Orders' => ['debit order', 'naedo', 'stop order', 'loan repayment', 'installment'],
            'Takeaways' => ['uber eats', 'mr d', 'nandos', 'kfc', 'steers', 'mcdonald', 'burger king', 'takeaway'],
            'Shopping' => ['takealot', 'makro', 'game', 'cotton on', 'ackermans', 'pep', 'clicks', 'dischem'],
            'Banking Fees' => ['bank fee', 'service fee', 'monthly fee', 'account fee', 'charges'],
            'Entertainment' => ['netflix', 'spotify', 'showmax', 'dstv', 'ster kinekor'],
            'Health' => ['doctor', 'clinic', 'pharmacy', 'medirite'],
        ];

        $needle = Str::lower($description);

        foreach ($rules as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($needle, $keyword)) {
                    return $category;
                }
            }
        }

        return 'Other';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $transactions
     */
    private function categoryBreakdown(Collection $transactions, float $spending): array
    {
        return $transactions
            ->where('type', 'expense')
            ->groupBy('category')
            ->map(function (Collection $items, string $category) use ($spending) {
                $amount = round(abs($items->sum('amount')), 2);

                return [
                    'name' => $category,
                    'amount' => $amount,
                    'percentage' => $spending > 0 ? round(($amount / $spending) * 100, 1) : 0,
                    'color' => self::CATEGORY_COLORS[$category] ?? self::CATEGORY_COLORS['Other'],
                    'transactions' => $items->count(),
                ];
            })
            ->sortByDesc('amount')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $transactions
     */
    private function recurringPayments(Collection $transactions): array
    {
        $recurringCategories = ['Airtime/Data', 'Banking Fees', 'Debit Orders', 'Entertainment', 'Insurance', 'Rent'];

        return $transactions
            ->where('type', 'expense')
            ->whereIn('category', $recurringCategories)
            ->groupBy('merchant')
            ->map(function (Collection $items, string $merchant) {
                $amount = round(abs($items->avg('amount')), 2);

                return [
                    'merchant' => $merchant,
                    'category' => $items->first()['category'],
                    'monthlyCost' => $amount,
                    'yearlyCost' => round($amount * 12, 2),
                    'lastSeen' => $items->sortByDesc('date')->first()['date'],
                    'paymentsSeen' => $items->count(),
                    'status' => $amount >= 500 ? 'review' : 'watch',
                ];
            })
            ->filter(fn (array $item) => $item['paymentsSeen'] >= 2)
            ->sortByDesc('yearlyCost')
            ->values()
            ->take(8)
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $transactions
     */
    private function monthlyTrends(Collection $transactions): array
    {
        return $transactions
            ->groupBy(fn (array $transaction) => Carbon::parse($transaction['date'])->format('Y-m'))
            ->map(function (Collection $items, string $month) {
                return [
                    'month' => Carbon::createFromFormat('Y-m', $month)->format('M'),
                    'income' => round($items->where('amount', '>', 0)->sum('amount'), 2),
                    'spending' => round(abs($items->where('amount', '<', 0)->sum('amount')), 2),
                ];
            })
            ->values()
            ->all();
    }

    private function goals(float $averageMonthlyNet): array
    {
        $recommendedEmergencyContribution = max(min($averageMonthlyNet * 0.45, 1800), 650);
        $recommendedLaptopContribution = max(min($averageMonthlyNet * 0.3, 1200), 450);

        return [
            [
                'name' => 'Emergency fund',
                'target' => 18000,
                'saved' => 6200,
                'deadline' => '2026-12-15',
                'recommendedMonthly' => round($recommendedEmergencyContribution, 2),
                'status' => 'On track with one spending trim',
            ],
            [
                'name' => 'New laptop',
                'target' => 14000,
                'saved' => 3500,
                'deadline' => '2026-10-31',
                'recommendedMonthly' => round($recommendedLaptopContribution, 2),
                'status' => 'Needs a smaller takeaway budget',
            ],
        ];
    }

    private function insights(array $categories, array $recurring, float $netCashFlow, float $opportunity): array
    {
        $topCategory = $categories[0] ?? ['name' => 'spending', 'amount' => 0];
        $yearlyRecurring = collect($recurring)->sum('yearlyCost');

        return [
            [
                'title' => 'Your biggest money lane',
                'body' => sprintf('%s is your largest expense category at %s this period.', $topCategory['name'], $this->rand($topCategory['amount'])),
                'tone' => 'neutral',
            ],
            [
                'title' => 'Debit order detective',
                'body' => sprintf('Recurring payments add up to about %s per year. Review the high-cost ones first.', $this->rand($yearlyRecurring)),
                'tone' => 'warning',
            ],
            [
                'title' => 'Practical saving move',
                'body' => sprintf('A 25%% trim on flexible categories could free roughly %s next month.', $this->rand($opportunity)),
                'tone' => 'positive',
            ],
            [
                'title' => 'Cash-flow check',
                'body' => $netCashFlow >= 0
                    ? sprintf('You ended positive by %s. Move part of that into a goal before it disappears into small swipes.', $this->rand($netCashFlow))
                    : sprintf('You are short by %s. Reduce flexible spending before taking on new commitments.', $this->rand(abs($netCashFlow))),
                'tone' => $netCashFlow >= 0 ? 'positive' : 'danger',
            ],
        ];
    }

    private function healthScore(float $savingsRate, array $recurring, float $opportunity): int
    {
        $score = 58 + min($savingsRate, 25) - min(count($recurring) * 2, 12) + min($opportunity / 250, 10);

        return (int) max(0, min(round($score), 100));
    }

    private function savingsOpportunity(array $categories): float
    {
        return round(collect($categories)
            ->whereIn('name', ['Takeaways', 'Shopping', 'Entertainment'])
            ->sum('amount') * 0.25, 2);
    }

    private function averageMonthlyNet(array $trends): float
    {
        if ($trends === []) {
            return 0;
        }

        return collect($trends)
            ->map(fn (array $month) => $month['income'] - $month['spending'])
            ->avg();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $transactions
     */
    private function periodLabel(Collection $transactions): string
    {
        if ($transactions->isEmpty()) {
            return 'No transactions yet';
        }

        $first = Carbon::parse($transactions->first()['date'])->format('d M Y');
        $last = Carbon::parse($transactions->last()['date'])->format('d M Y');

        return "{$first} - {$last}";
    }

    private function merchantName(string $description): string
    {
        $clean = preg_replace('/\b\d{2,}\b/', '', Str::lower($description)) ?? $description;
        $clean = preg_replace('/[^a-z0-9 ]/', ' ', $clean) ?? $clean;
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? $clean);

        if ($clean === '') {
            return 'Imported transaction';
        }

        return Str::title(implode(' ', array_slice(explode(' ', $clean), 0, 3)));
    }

    private function parseDate(string $value): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return now();
        }
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }

    private function normalizeKey(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower($value)) ?? '';
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function readValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function readAmount(array $row, array $keys): ?float
    {
        $value = $this->readValue($row, $keys);

        if ($value === null) {
            return null;
        }

        $negative = str_contains($value, '-') || (str_contains($value, '(') && str_contains($value, ')'));
        $normalised = trim(str_replace(['R', 'r', ' ', '(', ')'], '', $value));

        if (str_contains($normalised, ',') && ! str_contains($normalised, '.')) {
            $normalised = str_replace(',', '.', $normalised);
        } else {
            $normalised = str_replace(',', '', $normalised);
        }

        $normalised = preg_replace('/[^0-9.]/', '', $normalised) ?? '';

        if ($normalised === '') {
            return null;
        }

        $amount = (float) $normalised;

        return round($negative ? -abs($amount) : $amount, 2);
    }

    private function rand(float $amount): string
    {
        return 'R'.number_format($amount, 0, '.', ' ');
    }

    /**
     * @return array<int, array{date: string, description: string, amount: float}>
     */
    private function sampleTransactions(): array
    {
        return [
            ['date' => '2026-02-25', 'description' => 'Salary Deposit FinTech Studio', 'amount' => 24500],
            ['date' => '2026-02-01', 'description' => 'Rent Greenpoint Property', 'amount' => -7800],
            ['date' => '2026-02-02', 'description' => 'Checkers Hyper Groceries', 'amount' => -1430],
            ['date' => '2026-02-04', 'description' => 'Engen Fuel Cape Town', 'amount' => -890],
            ['date' => '2026-02-06', 'description' => 'Vodacom Contract Debit Order', 'amount' => -399],
            ['date' => '2026-02-08', 'description' => 'Netflix Subscription', 'amount' => -199],
            ['date' => '2026-02-12', 'description' => 'Mr D Food Takeaway', 'amount' => -246],
            ['date' => '2026-02-14', 'description' => 'Prepaid Electricity City Power', 'amount' => -950],
            ['date' => '2026-02-18', 'description' => 'Discovery Insurance', 'amount' => -1350],
            ['date' => '2026-02-22', 'description' => 'Standard Bank Monthly Fee', 'amount' => -72],
            ['date' => '2026-03-25', 'description' => 'Salary Deposit FinTech Studio', 'amount' => 24500],
            ['date' => '2026-03-01', 'description' => 'Rent Greenpoint Property', 'amount' => -7800],
            ['date' => '2026-03-02', 'description' => 'Pick n Pay Groceries', 'amount' => -1320],
            ['date' => '2026-03-05', 'description' => 'Shell Fuel Sea Point', 'amount' => -820],
            ['date' => '2026-03-06', 'description' => 'Vodacom Contract Debit Order', 'amount' => -399],
            ['date' => '2026-03-08', 'description' => 'Netflix Subscription', 'amount' => -199],
            ['date' => '2026-03-10', 'description' => 'Spotify Premium', 'amount' => -65],
            ['date' => '2026-03-12', 'description' => 'Uber Eats Takeaway', 'amount' => -315],
            ['date' => '2026-03-18', 'description' => 'Discovery Insurance', 'amount' => -1350],
            ['date' => '2026-03-20', 'description' => 'Takealot Online Shopping', 'amount' => -899],
            ['date' => '2026-03-22', 'description' => 'Standard Bank Monthly Fee', 'amount' => -72],
            ['date' => '2026-04-25', 'description' => 'Salary Deposit FinTech Studio', 'amount' => 24500],
            ['date' => '2026-04-01', 'description' => 'Rent Greenpoint Property', 'amount' => -7800],
            ['date' => '2026-04-03', 'description' => 'Woolworths Food Groceries', 'amount' => -1180],
            ['date' => '2026-04-04', 'description' => 'BP Fuel Observatory', 'amount' => -760],
            ['date' => '2026-04-06', 'description' => 'Vodacom Contract Debit Order', 'amount' => -399],
            ['date' => '2026-04-08', 'description' => 'Netflix Subscription', 'amount' => -199],
            ['date' => '2026-04-11', 'description' => 'KFC Takeaway', 'amount' => -184],
            ['date' => '2026-04-15', 'description' => 'Clicks Pharmacy', 'amount' => -420],
            ['date' => '2026-04-18', 'description' => 'Discovery Insurance', 'amount' => -1350],
            ['date' => '2026-04-22', 'description' => 'Standard Bank Monthly Fee', 'amount' => -72],
            ['date' => '2026-05-25', 'description' => 'Salary Deposit FinTech Studio', 'amount' => 24500],
            ['date' => '2026-05-01', 'description' => 'Rent Greenpoint Property', 'amount' => -7800],
            ['date' => '2026-05-03', 'description' => 'Shoprite Groceries', 'amount' => -1265],
            ['date' => '2026-05-05', 'description' => 'Engen Fuel Cape Town', 'amount' => -910],
            ['date' => '2026-05-06', 'description' => 'Vodacom Contract Debit Order', 'amount' => -399],
            ['date' => '2026-05-08', 'description' => 'Netflix Subscription', 'amount' => -199],
            ['date' => '2026-05-09', 'description' => 'Showmax Subscription', 'amount' => -99],
            ['date' => '2026-05-12', 'description' => 'Nandos Takeaway', 'amount' => -228],
            ['date' => '2026-05-14', 'description' => 'Prepaid Electricity City Power', 'amount' => -1050],
            ['date' => '2026-05-18', 'description' => 'Discovery Insurance', 'amount' => -1350],
            ['date' => '2026-05-20', 'description' => 'Cotton On Shopping', 'amount' => -680],
            ['date' => '2026-05-22', 'description' => 'Standard Bank Monthly Fee', 'amount' => -72],
        ];
    }
}
