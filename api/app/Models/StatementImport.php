<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StatementImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_profile_id',
        'source_filename',
        'bank_name',
        'status',
        'imported_at',
        'transaction_count',
        'total_income',
        'total_spending',
        'net_cash_flow',
        'savings_rate',
        'analysis_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
            'transaction_count' => 'integer',
            'total_income' => 'float',
            'total_spending' => 'float',
            'net_cash_flow' => 'float',
            'savings_rate' => 'float',
            'analysis_snapshot' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(FinancialProfile::class, 'financial_profile_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(FinancialGoal::class);
    }
}
