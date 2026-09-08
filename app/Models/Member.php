<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_no', 'firstname', 'lastname', 'middlename', 'plate_number',
        'operator_name', 'route', 'category', 'contact_number', 'address', 'photo_path',
        'date_joined', 'status', 'suspended_until', 'savings_balance', 'alkansiya_balance',
    ];

    protected $casts = [
        'date_joined' => 'date',
        'suspended_until' => 'date',
        'savings_balance' => 'decimal:2',
        'alkansiya_balance' => 'decimal:2',
    ];

    public const CATEGORIES = ['member', 'non-member'];

    public function getFullNameAttribute()
    {
        $middle = $this->middlename ? " {$this->middlename} " : ' ';

        return "{$this->firstname}{$middle}{$this->lastname}";
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path
            ? Storage::disk('public')->url($this->photo_path)
            : null;
    }

    public function dailyDues()
    {
        return $this->hasMany(DailyDue::class);
    }

    public function fuelConsumptions()
    {
        return $this->hasMany(FuelConsumption::class);
    }

    public function benefits()
    {
        return $this->hasMany(Benefit::class);
    }

    public function dependents()
    {
        return $this->hasMany(MemberDependent::class);
    }

    public function activeDependents()
    {
        return $this->hasMany(MemberDependent::class)->where('active', true);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoan()
    {
        return $this->hasOne(Loan::class)->whereIn('status', ['approved', 'active']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRoute($query, $route)
    {
        return $query->where('route', $route);
    }

    /** Full members only (excludes 'non-member' category). */
    public function scopeMembersOnly($query)
    {
        return $query->where('category', 'member');
    }

    public function isMember(): bool
    {
        return $this->category === 'member';
    }

    public function isTerminated(): bool
    {
        return $this->status === 'terminated';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isUnderSanction(): bool
    {
        return in_array($this->status, ['suspended', 'terminated'], true);
    }

    /**
     * The violation currently responsible for this member's sanctioned
     * standing, if any (latest un-lifted sanction).
     */
    public function currentSanction()
    {
        return $this->hasOne(Violation::class)
            ->whereNotNull('sanction')
            ->whereNull('sanction_lifted_at')
            ->latest('violation_date');
    }

    public function violations()
    {
        return $this->hasMany(Violation::class);
    }

    public function savingsLedgers()
    {
        return $this->hasMany(SavingsLedger::class, 'member_id');
    }

    /** Voluntary Alkansiya (SSS) contributions — separate from savings. */
    public function alkansiyaContributions()
    {
        return $this->hasMany(AlkansiyaContribution::class);
    }

    /**
     * How this member's savings balance was built up, from the savings
     * ledger — daily-dues deposits, fuel rebate, releases, and anything
     * that reduced it. Signed totals; the sum reconciles to
     * savings_balance.
     *
     * @return array{items: array<int, array{key: string, label: string, amount: float}>, total: float}
     */
    public function savingsBreakdown(): array
    {
        $labels = [
            'daily_dues' => 'Daily dues',
            'fuel_rebate' => 'Fuel rebate',
            'rebate_release' => "Member's share added",
            'dividend_release' => 'Annual dividend',
            'benefit_release' => 'Benefit (SSS) release',
            'savings_return' => 'Annual savings return (paid out)',
            'loan_deduction' => 'Loan repayment',
            'withdrawal' => 'Withdrawals',
            'adjustment' => 'Adjustments / corrections',
        ];

        $signed = $this->savingsLedgers()
            ->selectRaw('source_type, txn_type, SUM(amount) as total')
            ->groupBy('source_type', 'txn_type')
            ->get()
            ->reduce(function (array $carry, $row) {
                $amount = (float) $row->total * ($row->txn_type === 'withdrawal' ? -1 : 1);
                $carry[$row->source_type] = ($carry[$row->source_type] ?? 0) + $amount;

                return $carry;
            }, []);

        $items = [];
        foreach ($labels as $key => $label) {
            if (array_key_exists($key, $signed)) {
                $items[] = ['key' => $key, 'label' => $label, 'amount' => round($signed[$key], 2)];
            }
        }
        // Any source_type not in $labels (future-proofing).
        foreach ($signed as $key => $amount) {
            if (! isset($labels[$key])) {
                $items[] = ['key' => $key, 'label' => Str::of($key)->replace('_', ' ')->title()->value(), 'amount' => round($amount, 2)];
            }
        }

        return [
            'items' => $items,
            'total' => round(array_sum(array_column($items, 'amount')), 2),
        ];
    }
}
