<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Creator extends Model
{
    protected $primaryKey = 'handle';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'handle',
        'spoken_languages',
        'hair_color',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'spoken_languages' => 'array',
        'status' => 'string',
        'processed_at' => 'datetime',
    ];

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'creator_handle', 'handle');
    }
}
