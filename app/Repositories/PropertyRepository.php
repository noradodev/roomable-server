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
        if ($user->hasRole('admin')) {
            return Property::latest()->get();
        }

        if ($user->hasRole('landlord')) {
            return Property::where('landlord_id', $user->id)
                ->latest()
                ->get();
        }

        // if ($user->hasRole('tenant')) {
        //     return Property::whereHas('floors.rooms.tenants', function ($q) use ($user) {
        //         $q->where('tenant_id', $user->id);
        //     })->get();
        // }

        return collect();
    }
    public function createWithRelations(string $landlordId, array $data)
    {
        return DB::transaction(function () use ($landlordId, $data) {
            $property = Property::create([
                'landlord_id' => $landlordId,
                'name' => $data['name'],
                'address' => $data['address'],
                'city' => $data['city'],
                'image_url' => $data['image_url'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            if (!empty($data['floors'])) {
                foreach ($data['floors'] as $floorData) {
                    $floor = $property->floors()->create([
                        'name' => $floorData['name'] ?? 'Unnamed Floor',
                        'floor_number' => $floorData['floor_number'] ?? 0,
                    ]);

                    if (!empty($floorData['rooms'])) {
                        foreach ($floorData['rooms'] as $roomData) {
                            $floor->rooms()->create([
                                'room_number' => $roomData['room_number'],
                                'room_type' => $roomData['room_type'],
                                'price' => $roomData['price'] ?? 0,
                                'status' => $roomData['status'] ?? 'available',
                            ]);
                        }
                    }
                }
            }

            return $property->load('floors.rooms');
        });
    }
}
