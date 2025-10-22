<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandlordPaymentFile extends Model
{
    protected $fillable = [
        'landlord_payment_method_id',
        'file_type',
        'original_name',
        'storage_path',
        'file_url',
        'file_size',
        'mime_type',
    ];
}
