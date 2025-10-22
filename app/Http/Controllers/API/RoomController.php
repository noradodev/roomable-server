<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRequest;
use App\Http\Requests\Room\UpdateRequest;
use App\Http\Responses\ApiResponser;
use App\Interfaces\RoomRepositoryInterface;
use App\Models\Room;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RoomController extends Controller
{
    protected RoomRepositoryInterface $roomRepo;

    public function __construct(RoomRepositoryInterface $roomRepo)
    {
        $this->roomRepo = $roomRepo;
    }

    public function index(?string $floorId = null)
    {
        $user = Auth::user();
        $rooms = $this->roomRepo->index($user, $floorId);
        return ApiResponser::ok(['rooms' => $rooms]);
    }

    public function store(StoreRequest $request)
    {
        try {
            $this->roomRepo->store($request->validated());
            return ApiResponser::created([]);
        } catch (\Throwable $e) {
            Log::error('Room creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create room'], 500);
        }
    }

    public function update(UpdateRequest $request, Room $room)
    {   
        try {
            $room = $this->roomRepo->update($request->validated(), $room->id);
            return response()->json($room);
        } catch (\Exception $e) {
            return ApiResponser::error($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Room update failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update room'], 500);
        }
    }

    public function destroy($id)
    {
        $this->roomRepo->destroy($id);
        return ApiResponser::ok(['message' => 'Room deleted successfully']);
    }

    public function show($id)
    {
        try {
            $room = Room::with(['floor.property.landlord'])
                ->findOrFail($id);

            if (Auth::id() !== $room->floor->property->landlord_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this room'
                ], 403);
            }

            $room->price = (float) $room->price;

            return response()->json([
                'success' => true,
                'data' => $room
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Room not found'
            ], 404);
        }
    }
}
