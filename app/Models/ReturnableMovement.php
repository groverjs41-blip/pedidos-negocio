<?php

namespace App\Models;

use App\Enums\ReturnableMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnableMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_token',
        'customer_id',
        'order_id',
        'returnable_type_id',
        'movement_type',
        'quantity',
        'occurred_at',
        'user_id',
        'notes',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'movement_type' => ReturnableMovementType::class,
        'quantity' => 'integer',
        'occurred_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(ReturnableType::class, 'returnable_type_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }
}
