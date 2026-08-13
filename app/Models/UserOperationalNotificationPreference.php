<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOperationalNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'in_app_enabled',
        'sound_enabled',
        'browser_enabled',
    ];

    protected function casts(): array
    {
        return [
            'in_app_enabled' => 'boolean',
            'sound_enabled' => 'boolean',
            'browser_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
