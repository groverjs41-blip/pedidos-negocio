<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnableType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'active' => 'boolean',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(ReturnableMovement::class);
    }
}
