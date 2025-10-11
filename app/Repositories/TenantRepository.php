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
        $query = Tenant::with('room.floor.property');

        if ($user->hasRole('landlord')) {
            $query->where('landlord_id', $user->id);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['property_id'])) {
            $query->whereHas('room.floor.property', function ($q) use ($filters) {
                $q->where('id', $filters['property_id']);
            });
        }

        if (!empty($filters['move_in_from']) && !empty($filters['move_in_to'])) {
            $query->whereBetween('move_in_date', [
                $filters['move_in_from'],
                $filters['move_in_to'],
            ]);
        }

        $perPage = $filters['per_page'] ?? 10;

        return $query->latest()->paginate($perPage);
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['room_id'] = $data['room_id'] ?? null;
            $data['status']  = $data['room_id']   ? Tenant::STATUS_ACTIVE : Tenant::STATUS_UNASSIGNED;

            return Tenant::create($data);
        });
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
