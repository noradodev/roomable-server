<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantTelegramLinkToken extends Model
{
  protected $fillable = [
    'tenant_id',
    'token'
  ];

  public function tanant(): BelongsTo
  {
    return $this->belongsTo(Tenant::class);
  }

  public function getRouteKeyName(): string
  {
    return 'id';
  }
  public function telegramToken()
  {
    return $this->hasOne(TenantTelegramLinkToken::class);
  }
}
