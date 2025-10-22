<?php

namespace App\Repositories;

use App\Interfaces\TenantRepositoryInterface;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class TenantRepository implements TenantRepositoryInterface
{
    /**
     * Create a new class instance.
     */
    public function index($user, array $filters = [])
    {
        $query = Tenant::with('currentRoom.floor.property');

        if ($user->hasRole('landlord')) {
            $query->where('landlord_id', $user->id);
        }

        $query->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null)
            ->byProperty($filters['property_id'] ?? null)
            ->moveInDateRange(
                $filters['move_in_from'] ?? null,
                $filters['move_in_to'] ?? null
            );

        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    public function store(array $data)
    {
        return Tenant::create($data);
    }

    public function update(array $data, string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->update($data);
        return $tenant->fresh();
    }

    public function assignRoom(string $tenantId, string $roomId, array $meta = [])
    {
        return DB::transaction(function () use ($tenantId, $roomId, $meta) {
            $tenant = Tenant::findOrFail($tenantId);

            if ($tenant->status === 'active' && $tenant->room_id) {
                throw new \Exception('Tenant already assigned to a room.');
            }

            $tenant->update([
                'room_id' => $roomId,
                'status' => 'active',
                'move_in_date' => $meta['move_in_date'] ?? now(),
            ]);

            return $tenant->fresh(['room.floor.property']);
        });
    }

    public function delete(string $id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->delete();
    }
}
