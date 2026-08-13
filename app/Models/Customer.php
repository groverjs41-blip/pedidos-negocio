<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'location_notes',
        'notes',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    /**
     * Get the orders for the customer.
     */
    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the payments for the customer.
     */
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Calculate the total customer debt derived from DELIVERED orders using BCMath.
     */
    public function outstandingBalance(): string
    {
        $sum = '0.00';
        $deliveredOrders = $this->orders()
            ->where('status', \App\Enums\OrderStatus::DELIVERED)
            ->get();

        foreach ($deliveredOrders as $order) {
            $balance = $order->outstandingBalance();
            $sum = bcadd($sum, $balance, 2);
        }

        return $sum;
    }
}
