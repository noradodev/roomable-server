<?php

namespace App\Repositories;

use App\Interfaces\PropertyRepositoryInterface;
use App\Models\Property;
use Illuminate\Support\Facades\DB;

class PropertyRepository implements PropertyRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function index()
    {
        return Property::all();
    }

    public function show($user, $propertyId)
    {
        $query = Property::with(['floors' => function ($q) {
            $q->orderBy('floor_number', 'asc')->with('rooms');
        }])
            ->where('id', $propertyId);

        if ($user->hasRole('landlord')) {
            $query->where('landlord_id', $user->id);
        }
        $property = $query->firstOrFail();

        return $property;
    }


    public function store(array $data)
    {
        return Property::create($data);
    }

    public function update(array $data, $id)
    {
        return Property::whereId($id)->update($data);
    }

    public function delete($id)
    {
        return Property::destroy($id);
    }
    public function forUser($user)
    {
        $query = Property::withCount(['floors as floors_count'])
            ->addSelect([
                'total_rooms' => DB::table('rooms')
                    ->join('floors', 'rooms.floor_id', '=', 'floors.id')
                    ->whereColumn('floors.property_id', 'properties.id')
                    ->selectRaw('COUNT(*)'),
                'renting_rooms' => DB::table('rooms')
                    ->join('floors', 'rooms.floor_id', '=', 'floors.id')
                    ->whereColumn('floors.property_id', 'properties.id')
                    ->whereNotNull('rooms.current_tenant_id')
                    ->selectRaw('COUNT(*)'),
                'remaining_rooms' => DB::table('rooms')
                    ->join('floors', 'rooms.floor_id', '=', 'floors.id')
                    ->whereColumn('floors.property_id', 'properties.id')
                    ->whereNull('rooms.current_tenant_id')
                    ->selectRaw('COUNT(*)')
            ])->latest();

        if ($user->hasRole('admin')) {
            return $query->get();
        }

        if ($user->hasRole('landlord')) {
            return $query->where('landlord_id', $user->id)->get();
        }

        return collect();
    }
    public function createWithRelations(string $landlordId, array $data)
    {
        return DB::transaction(function () use ($landlordId, $data) {
            $property = Property::create([
                'landlord_id' => $landlordId,
                'name' => $data['property']['name'],
                'address' => $data['property']['address'],
                'city' => $data['property']['city'],
                'image_url' => $data['property']['image_url'] ?? null,
                'description' => $data['property']['description'] ?? null,
            ]);

            if (!empty($data['roomSetup']['floors'])) {
                foreach ($data['roomSetup']['floors'] as $floorData) {
                    $floor = $property->floors()->create([
                        'name' => $floorData['name'] ?? 'Unnamed Floor',
                        'floor_number' => $floorData['number'] ?? 0,
                    ]);

                    // Check if floor has rooms
                    if (!empty($floorData['rooms'])) {
                        foreach ($floorData['rooms'] as $roomData) {
                            $floor->rooms()->create([
                                'room_number' => $roomData['roomNumber'],
                                'room_type' => $roomData['type'],
                                'price' => $roomData['price'] ?? 0,
                                'status' => 'available',
                            ]);
                        }
                    }
                }
            }

            return $property->load('floors.rooms');
        });
    }
}
