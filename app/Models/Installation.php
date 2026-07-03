<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installation extends Model
{
    public function productNotes()
    {
        return $this->hasMany(\App\Models\ProductNote::class, 'installation_id', 'id');
    }
    protected $fillable = [
        'customer_id',
        'product_id',
        'sale_id',
        'assigned_to',
        'title',
        'location',
        'due_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function tasks(): HasMany { return $this->hasMany(InstallationTask::class)->orderBy('sort_order'); }
    public function attachments(): HasMany { return $this->hasMany(EngineerAttachment::class); }
    public function defectReports(): HasMany { return $this->hasMany(DefectReport::class); }
    public function feedbacks(): HasMany { return $this->hasMany(ClientFeedback::class); }
    public function signature() { return $this->hasOne(Signature::class); }
    public function sale(): BelongsTo { return $this->belongsTo(\App\Models\Sale::class); }
}


