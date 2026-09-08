<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberDependent extends Model
{
    protected $fillable = ['member_id', 'name', 'relationship', 'birthdate', 'active'];

    protected $casts = [
        'birthdate' => 'date',
        'active' => 'boolean',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function benefits()
    {
        return $this->hasMany(Benefit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
