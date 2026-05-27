<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'display_name',
        'bank_name',
        'currency',
        'privacy_mode',
    ];

    protected function casts(): array
    {
        return [
            'privacy_mode' => 'boolean',
        ];
    }

    public function imports(): HasMany
    {
        return $this->hasMany(StatementImport::class);
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
