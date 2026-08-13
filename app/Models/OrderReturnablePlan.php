<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturnablePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'returnable_type_id',
        'quantity',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function returnableType(): BelongsTo
    {
        return $this->belongsTo(ReturnableType::class);
    }
}
