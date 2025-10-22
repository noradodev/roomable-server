<?php

namespace App\Repositories;

use App\Interfaces\RoomRepositoryInterface;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RoomRepository implements RoomRepositoryInterface
{
    public function index($user, ?string $floorId = null)
    {

        $query = Room::with(['tenant', 'floor.property'])->latest();

        if ($user->hasRole('admin')) {
        }

        if ($user->hasRole('landlord')) {
            $query->whereHas(
                'floor.property',
                fn($q) =>
                $q->where('landlord_id', $user->id)
            );
        }

        if ($floorId) {
            $query->where('floor_id', $floorId);
        }

        return $query->paginate(10);
    }


    public function store(array $data)
    {
        return DB::transaction(fn() => Room::create($data));
    }

    public function update(array $data, $id)
    {
        $room = Room::findOrFail($id);

        if (array_key_exists('current_tenant_id', $data)) {
            $tenantId = $data['current_tenant_id'];

            if ($tenantId) {
                $existingRoom = Room::where('current_tenant_id', $tenantId)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingRoom) {
                    throw new \Exception('This tenant is already assigned to another room.');
                }

                $data['status'] = 'occupied';
            } else {
                $data['status'] = 'available';
            }
        }

        $room->update($data);

        return $room->fresh(['tenant']);
    }


    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();
        return true;
    }
    public function assignRoom(string $roomId, string $tenantId, string $landlordId): ?Room
    {
        $room = Room::with('floor.property')->find($roomId);
        $tenant = Tenant::find($tenantId);

        if (!$room || !$tenant) {
            throw new ModelNotFoundException("Room or Tenant not found for assignment.");
        }


        $roomOwnerId = $room->floor->property->landlord_id ?? null;

        if ($roomOwnerId !== $landlordId) {
            throw new InvalidArgumentException(
                "Authorization Failed: The authenticated user does not own this room/property."
            );
        }

        $tenantOwnerId = $tenant->landlord_id ?? null;

        if ($tenantOwnerId !== $landlordId) {
            throw new InvalidArgumentException(
                "Authorization Failed: The authenticated user does not own this tenant record."
            );
        }

        if ($room->status !== 'available') {
            throw new InvalidArgumentException("Room is not available for assignment (Current Status: {$room->status}).");
        }

        try {
            return DB::transaction(function () use ($room, $tenant) {

                $room->current_tenant_id = $tenant->id;
                $room->status = 'occupied';
                $room->save();

                $tenant->room_id = $room->id;
                $tenant->save();

                return $room;
            });
        } catch (\Exception $e) {
            logger()->error("Failed to assign room $roomId to tenant $tenantId: " . $e->getMessage());
            return null;
        }
    }
}
