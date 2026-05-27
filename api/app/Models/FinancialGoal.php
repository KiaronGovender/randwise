<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_profile_id',
        'statement_import_id',
        'name',
        'target_amount',
        'saved_amount',
        'deadline',
        'recommended_monthly',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'float',
            'saved_amount' => 'float',
            'deadline' => 'date',
            'recommended_monthly' => 'float',
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
