<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivedPost extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function feed(): BelongsTo
    {
        return $this->belongsTo(Feed::class);
    }
}
