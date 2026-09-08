<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
        'template', 'filename', 'preview', 'imported', 'skipped', 'errors', 'user_id',
    ];

    protected $casts = [
        'preview' => 'boolean',
        'errors' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getTemplateLabelAttribute(): string
    {
        return match ($this->template) {
            'members' => 'Members',
            'daily_collection' => 'Daily Collection',
            'expenses' => 'Expenses',
            'rental' => 'Rental Income',
            default => ucfirst($this->template),
        };
    }
}
