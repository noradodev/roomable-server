<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tenant = $this->whenLoaded('tenant');
        $room = $this->whenLoaded('tenant', fn () => $this->tenant->room ?? null);

        return [
            'id' => $this->id,
            'tenant_name' => $tenant ? ($tenant->name ?? '-') : '-',
            'room_number' => $room ? ($room->room_number ?? '-') : '-',

            'amount' => (float) $this->amount, 
            'electricity_cost' => (float) $this->electricity_cost,
            'water_cost' => (float) $this->water_cost,
            'total_amount' => (float) $this->total_amount,

            'status' => $this->status,
            'month_years' => $this->month_years, 
            'method' => $this->method,
            'note' => $this->note,
            'rejection_reason' => $this->rejection_reason,
            
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'paid_at' => $this->paid_at ? Carbon::parse($this->paid_at)->toDateTimeString() : null,
            'due_date' => $this->due_date ? Carbon::parse($this->due_date)->toDateString() : null,

            'proof_of_payment_url' => $this->proof_of_payment, 
            
        ];
    }
}