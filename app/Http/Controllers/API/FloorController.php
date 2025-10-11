<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Floor\StoreRequest;
use App\Http\Requests\Floor\UpdateRequest;
use App\Http\Responses\ApiResponser;
use App\Interfaces\FloorRepositoryInterface;
use App\Models\Floor;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FloorController extends Controller
{
    protected FloorRepositoryInterface $floorRepo;

    public function __construct(FloorRepositoryInterface $floorRepo)
    {
        $this->floorRepo = $floorRepo;
    }

    public function index()
    {
        $user = Auth::user();
        $floors = $this->floorRepo->index($user);
        return ApiResponser::ok(['floors' => $floors]);
    }

    public function store(StoreRequest $request)
    {
        try {
            $floor = $this->floorRepo->store($request->validated());
            return ApiResponser::created([]);
        } catch (\Throwable $e) {
            Log::error('Floor creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create floor'], 500);
        }
    }


    public function update(UpdateRequest $request, Floor $floor)
    {
        try {
            $floor = $this->floorRepo->update($request->validated(), $floor->id);
            return ApiResponser::ok([]);
        } catch (\Throwable $e) {
            Log::error('Floor update failed', ['error' => $e->getMessage()]);
            return ApiResponser::error('Error Updating Floor');
        }
    }

    public function destroy($id)
    {
        $this->floorRepo->destroy($id);
        return ApiResponser::ok(['message' => 'Floor deleted successfully']);
    }
}
