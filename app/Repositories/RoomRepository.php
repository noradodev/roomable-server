<?php

namespace App\Repositories;

use App\Interfaces\RoomRepositoryInterface;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

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
        $room->update($data);
        return $room;
    }

    public function destroy($id)
    {
        $room = Room::findOrFail($id);
        $room->delete();
        return true;
    }
}
