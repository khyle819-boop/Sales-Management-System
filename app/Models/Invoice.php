<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'sale_id',
        'subtotal',
        'tax',
        'total',
        'notes',
        'status',
        'due_date',
        'paid_date'
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function getFormattedSubtotalAttribute()
    {
        return '$' . number_format($this->subtotal, 2);
    }

    public function getFormattedTaxAttribute()
    {
        return '$' . number_format($this->tax, 2);
    }

    public function getFormattedTotalAttribute()
    {
        return '$' . number_format($this->total, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            'draft' => 'badge-secondary',
            'sent' => 'badge-info',
            'paid' => 'badge-success',
            'overdue' => 'badge-danger'
        ];

        return $badges[$this->status] ?? 'badge-secondary';
    }
}
