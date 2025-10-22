<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramLinkToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at'
    ];

    public function user(): BelongsTo
     {
        return $this->belongsTo(User::class);
    }
        protected $casts = [
        'expires_at' => 'datetime', 
    ];
}
