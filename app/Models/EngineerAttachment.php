<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngineerAttachment extends Model
{
    protected $fillable = ['installation_id','product_id','path','type','description'];

    public function installation(): BelongsTo { return $this->belongsTo(Installation::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}


