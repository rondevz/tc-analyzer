<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    protected $fillable = [
        'creator_handle',
        'tiktok_id',
        'tiktok_url',
        'status',
        'transcript',
        'audio_class',
        'frame_path',
        'error_message',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Creator::class, 'creator_handle', 'handle');
    }
}
