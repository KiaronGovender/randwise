<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_profile_id',
        'statement_import_id',
        'occurred_on',
        'description',
        'merchant',
        'amount',
        'category',
        'type',
        'is_recurring_candidate',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'amount' => 'float',
            'is_recurring_candidate' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(FinancialProfile::class, 'financial_profile_id');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(StatementImport::class, 'statement_import_id');
    }
}
