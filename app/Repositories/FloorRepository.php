<?php

namespace App\Repositories;

use App\Interfaces\FloorRepositoryInterface;
use App\Models\Floor;
use Illuminate\Support\Facades\DB;
use App\Models\Property;

class FloorRepository implements FloorRepositoryInterface
{
    public function index($user)
    {
        $query = Floor::with('property')->latest();

        if ($user->hasRole('landlord')) {
            $query->whereHas(
                'property',
                fn($q) =>
                $q->where('landlord_id', $user->id)
            );
        }

        if ($user->hasRole('admin')) {
        }

        return $query->paginate(10);
    }
  
    public function store(array $data)
    {
        return DB::transaction(fn() => Floor::create($data));
    }

    public function update(array $data, $id)
    {
        $floor = Floor::findOrFail($id);
        $floor->update($data);
        return $floor;
    }

    public function destroy($id)
    {
        $floor = Floor::findOrFail($id);
        $floor->delete();
        return true;
    }
}
