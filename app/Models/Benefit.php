<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Benefit extends Model
{
    protected $fillable = [
        'member_id', 'beneficiary_type', 'member_dependent_id',
        'benefit_type', 'amount', 'claim_date', 'days',
        'status', 'remarks', 'processed_by', 'processed_date',
    ];

    protected $casts = [
        'claim_date' => 'date',
        'processed_date' => 'date',
    ];

    /**
     * Fallback amounts, used when the matching Setting row is missing.
     * Live values: benefit_primary_daily / benefit_dependent_daily (per
     * day, hospitalization), benefit_burial, benefit_sss (flat).
     */
    public const RATES = [
        'hospitalization' => 500.00,
        'burial' => 3000.00,
        'sss' => 700.00,
    ];

    /** Per-day hospitalization rate for the given beneficiary type. */
    public static function dailyRate(string $beneficiaryType): float
    {
        return $beneficiaryType === 'dependent'
            ? (float) Setting::get('benefit_dependent_daily', 300.00)
            : (float) Setting::get('benefit_primary_daily', self::RATES['hospitalization']);
    }

    public static function flatRate(string $benefitType): float
    {
        return match ($benefitType) {
            'burial' => (float) Setting::get('benefit_burial', self::RATES['burial']),
            'sss' => (float) Setting::get('benefit_sss', self::RATES['sss']),
            default => 0.0,
        };
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function dependent()
    {
        return $this->belongsTo(MemberDependent::class, 'member_dependent_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function getBeneficiaryNameAttribute(): string
    {
        return $this->beneficiary_type === 'dependent'
            ? ($this->dependent?->name ?? 'Dependent')
            : ($this->member?->full_name ?? 'Member');
    }
}
