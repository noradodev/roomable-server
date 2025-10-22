<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandlordPaymentConfiguration extends Model
{
    protected $fillable = [
        'payment_method_id',
        'instructions',
        'collector_name',
        'collection_location',
        'account_name',
        'account_number'
    ];

    public function paymentMethod()
    {
        return $this->belongsTo(LandlordPaymentMethod::class);
    }
}
