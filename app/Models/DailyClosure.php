<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_date',
        'closed_at',
        'closed_by',
        'forced',
        'force_reason',
        'notes',
        'snapshot',
    ];

    protected $casts = [
        'business_date' => 'date',
        'closed_at' => 'datetime',
        'forced' => 'boolean',
        'snapshot' => 'array',
    ];

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
