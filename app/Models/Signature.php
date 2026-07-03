<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    protected $fillable = ['installation_id','client_name','image_path'];

    public function installation(): BelongsTo { return $this->belongsTo(Installation::class); }
}


