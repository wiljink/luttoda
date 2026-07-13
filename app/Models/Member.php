<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Member extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'member_no', 'firstname', 'lastname', 'middlename', 'plate_number',
        'operator_name', 'route', 'contact_number', 'address',
        'date_joined', 'status', 'savings_balance',
    ];

    protected $casts = [
        'date_joined' => 'date',
        'savings_balance' => 'decimal:2',
    ];

    public function getFullNameAttribute()
    {
        $middle = $this->middlename ? " {$this->middlename} " : ' ';
        return "{$this->firstname}{$middle}{$this->lastname}";
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
    public function violations()
    {
    return $this->hasMany(Violation::class);
    }
}
