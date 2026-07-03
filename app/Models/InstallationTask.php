<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallationTask extends Model
{
    protected $fillable = [
        'installation_id',
        'title',
        'is_done',
        'sort_order',
    ];

    protected $casts = [
        'is_done' => 'boolean',
    ];

    public function installation(): BelongsTo { return $this->belongsTo(Installation::class); }
}


