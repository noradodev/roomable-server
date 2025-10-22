<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandlordPaymentMethod extends Model
{
    protected $fillable = [
        'landlord_id',
        'payment_type_id',
        'is_enabled',
        'is_active'
    ];
    public function configuration()
    {
        return $this->hasOne(LandlordPaymentConfiguration::class, 'payment_method_id');
    }

    public function files()
    {
        return $this->hasMany(LandlordPaymentFile::class);
    }

    public function getCollectorNameAttribute()
    {
        return $this->configuration->collector_name;
    }

    public function getQrAccountNameAttribute()
    {
        return $this->configuration->account_name;
    }

    public function getQrImageUrlAttribute()
    {
        return $this->files->where('file_type', 'qr_code')->first()?->file_url;
    }
    public function methodType()
    {
        return $this->belongsTo(LandlordPaymentType::class, 'payment_type_id');
    }
}
