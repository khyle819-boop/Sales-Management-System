<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefectReport extends Model
{
    protected $fillable = ['installation_id','product_id','reported_by','description','severity','status'];

    public function installation(): BelongsTo { return $this->belongsTo(Installation::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by'); }
}


