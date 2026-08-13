<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'submission_token',
        'customer_id',
        'customer_name_snapshot',
        'customer_phone_snapshot',
        'delivery_address_snapshot',
        'status',
        'subtotal',
        'total',
        'notes',
        'created_by',
        'delivery_user_id',
        'ordered_at',
        'preparing_at',
        'ready_at',
        'delivering_at',
        'delivered_at',
        'cancelled_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'ordered_at' => 'datetime',
            'preparing_at' => 'datetime',
            'ready_at' => 'datetime',
            'delivering_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Get the customer associated with the order.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user who created the order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who is delivering the order.
     */
    public function deliveryUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_user_id');
    }

    /**
     * Get the items for the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the status history of the order.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * Get the payment allocations for the order.
     */
    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Get the total paid amount for valid (non-voided) payments using BCMath.
     */
    public function paidAmount(): string
    {
        $sum = '0.00';
        $allocations = $this->paymentAllocations()->whereHas('payment', function ($query) {
            $query->whereNull('voided_at');
        })->get();

        foreach ($allocations as $alloc) {
            $sum = bcadd($sum, number_format((float)$alloc->amount, 2, '.', ''), 2);
        }

        return $sum;
    }

    /**
     * Get the outstanding balance for the order using BCMath.
     */
    public function outstandingBalance(): string
    {
        $total = number_format((float)$this->total, 2, '.', '');
        $paid = $this->paidAmount();
        $balance = bcsub($total, $paid, 2);

        return bccomp($balance, '0.00', 2) < 0 ? '0.00' : $balance;
    }

    /**
     * Get the payment status enum for the order.
     */
    public function paymentStatus(): \App\Enums\PaymentStatus
    {
        $paid = $this->paidAmount();
        $total = number_format((float)$this->total, 2, '.', '');

        if (bccomp($paid, '0.00', 2) === 0) {
            return \App\Enums\PaymentStatus::PENDING;
        }

        if (bccomp($paid, $total, 2) >= 0) {
            return \App\Enums\PaymentStatus::PAID;
        }

        return \App\Enums\PaymentStatus::PARTIAL;
    }
}
