<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    protected $fillable = [
        'name',
        'supplier',
        'material',
        'description',
        'price',
        'img',
        'document',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2'
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(\App\Models\ProductNote::class);
    }

    public function getProductSalesCountAttribute()
    {
        return $this->sales()->where('type', 'Product')->count();
    }

    public function getServiceSalesCountAttribute()
    {
        return $this->sales()->where('type', 'Service')->count();
    }

    public function getFormattedPriceAttribute()
    {
        return '$' . number_format($this->price, 2);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
