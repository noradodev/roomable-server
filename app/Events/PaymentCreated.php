<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;

    public function __construct($payment)
    {
        $this->payment = $payment;
        
        Log::info('PaymentCreated Event - Raw payment data:', [
            'payment_id' => $payment->id,
            'tenant_id' => $payment->tenant_id,
            'payment_object' => $payment->toArray()
        ]);
    }

    public function broadcastOn()
    {
        $this->payment->load([
            'tenant.room.floor.property.user'
        ]);

        try {
            Log::info('Relationship chain debug:', [
                'tenant' => $this->payment->tenant ? 'exists' : 'null',
                'room' => $this->payment->tenant->room ? 'exists' : 'null',
                'floor' => $this->payment->tenant->room->floor ? 'exists' : 'null',
                'property' => $this->payment->tenant->room->floor->property ? 'exists' : 'null',
                'user' => $this->payment->tenant->room->floor->property->user ? 'exists' : 'null',
                'landlord_id' => $this->payment->tenant->room->floor->property->user->id ?? 'NOT FOUND'
            ]);

            $landlordId = $this->payment->tenant->room->floor->property->landlord_id;
            
            Log::info('Broadcasting to channel:', [
                'channel' => 'landlord.' . $landlordId,
                'landlord_uuid' => $landlordId
            ]);
            
            return new PrivateChannel('Landlord.'. $landlordId);
            
        } catch (\Exception $e) {
            Log::error('Error getting landlord ID:', [
                'error' => $e->getMessage(),
                'payment_id' => $this->payment->id
            ]);
            
            return new PrivateChannel('Landlord.fallback');
        }
    }
       public function broadcastAs()
    {
        return 'PaymentCreated';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->payment->id,
            'tenant' => $this->payment->tenant->name,
            'amount' => $this->payment->amount,
            'status' => $this->payment->status,
            'month' => $this->payment->month_years,
        ];
    }
}