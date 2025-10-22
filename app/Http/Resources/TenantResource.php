<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $roomInfo = null;
        if ($this->whenLoaded('currentRoom') && $this->currentRoom) {
            $room = $this->currentRoom;
            $property = $room->floor->property ?? null;

            $roomInfo = [
                'room_number' => $room->room_number,
                'property_name' => $property->name ?? 'Unassigned Property',
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'move_in_date' => $this->move_in_date,
            'move_out_date' => $this->move_out_date,
            'telegram_id' => $this->telegram_id,
            'note'=> $this->notes,
            'status'=>$this->status,
            'due_date' => $this->due_date,
            'current_tenancy' => $this->when($roomInfo !== null, $roomInfo),
        ];
    }
}
