<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'img',
        'status'
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function getProductPurchasesCountAttribute()
    {
        return $this->sales()->where('type', 'Product')->count();
    }

    public function getServiceAvailedCountAttribute()
    {
        return $this->sales()->where('type', 'Service')->count();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
