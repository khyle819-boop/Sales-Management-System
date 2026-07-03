<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'customer_id',
        'product_id',
        'type',
        'reference_number',
        'description',
        'amount',
        'status',
        'notes',
        'payment_proof',
        'due_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedAmountAttribute()
    {
        return $this->amount ? '$' . number_format($this->amount, 2) : 'N/A';
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'pending' => 'badge-warning',
            'processing' => 'badge-info',
            'completed' => 'badge-success',
            'cancelled' => 'badge-danger'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }

    public function getTypeBadgeAttribute()
    {
        $badges = [
            'inquiry' => 'badge-primary',
            'order' => 'badge-success',
            'payment' => 'badge-info'
        ];

        return $badges[$this->type] ?? 'badge-secondary';
    }
}
