<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreRequest;
use App\Http\Requests\Tenant\UpdateRequest;
use App\Http\Resources\TenantCollection;
use App\Http\Responses\ApiResponser;
use App\Interfaces\TenantRepositoryInterface;
use App\Models\Tenant;
use App\Models\TenantTelegramLinkToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class TenantController extends Controller
{
    protected TenantRepositoryInterface $tenantRepository;

    public function __construct(TenantRepositoryInterface $tenantRepository)
    {
        $this->tenantRepository = $tenantRepository;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $filters = $request->all();

        $tenantsPaginator = $this->tenantRepository->index($user, $filters);

        $structuredData = (new TenantCollection($tenantsPaginator))->toArray($request);

        return ApiResponser::ok(
            data: $structuredData,
            successMessage: 'Tenants retrieved successfully'
        );
    }

    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $data['landlord_id'] = Auth::id();

        try {
            $tenant = $this->tenantRepository->store($data);
            return ApiResponser::created([
                'message' => 'Tenant created successfully',
                'tenant'  => $tenant,
            ]);
        } catch (Throwable $ex) {
            Log::error('Tenant creation failed', ['error' => $ex]);
            return ApiResponser::serverError('Failed to create tenant');
        }
    }

    public function update(UpdateRequest $request, Tenant $tenant)
    {
        $data = $request->validated();
        try {
            $tenant = $this->tenantRepository->update($data, $tenant->id);
            return ApiResponser::ok([
                'message' => 'Tenant updated successfully',
                'tenant'  => $tenant,
            ]);
        } catch (Throwable $ex) {
            Log::error('Tenant update failed', ['error' => $ex]);
            return ApiResponser::serverError('Failed to update tenant');
        }
    }

    public function assignRoom(string $tenantId)
    {
        request()->validate([
            'room_id' => 'required|uuid|exists:rooms,id',
            'move_in_date' => 'nullable|date',
        ]);

        try {
            $tenant = $this->tenantRepository->assignRoom(
                $tenantId,
                request('room_id'),
                ['move_in_date' => request('move_in_date')]
            );

            return ApiResponser::ok([
                'message' => 'Tenant assigned successfully',
                'tenant' => $tenant,
            ]);
        } catch (Throwable $ex) {
            Log::error('Tenant assign failed', ['error' => $ex]);
            return ApiResponser::serverError('Failed to assign tenant to room');
        }
    }

    public function show(string $id)
    {
        $landlordId = Auth::id();

        $tenant = Tenant::where('id', $id)
            ->with([
                'room.floor.property'
            ])
            ->first();

        if (!$tenant) {
            return response()->json([
                'message' => 'Tenant not found'
            ], 404);
        }

        if ($tenant->room) {
            if (!$tenant->room->floor || !$tenant->room->floor->property) {
                return response()->json([
                    'message' => 'Room is not properly assigned to a property'
                ], 403);
            }

            if ($tenant->room->floor->property->landlord_id != $landlordId) {
                return response()->json([
                    'message' => 'You do not have permission to view this tenant'
                ], 403);
            }
        } else {

            Log::debug("Tenant has no room assigned - allowing access");
        }
        $telegramToken = TenantTelegramLinkToken::where('tenant_id', $id)->first();
        $telegramLink = null;

        if ($telegramToken) {
            $telegramLink = "https://t.me/roomable_bot?start=" . $telegramToken->token;
        }
        $tenantData = [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => $tenant->phone,
            'notes' => $tenant->notes,
            'move_in_date' => $tenant->move_in_date,
            'due_date' => $tenant->due_date,
            'status' => $tenant->status,
            'telegram_id' => $tenant->telegram_id,
            'telegram_chat_id' => $tenant->telegram_chat_id,
            'telegram_link' => $telegramLink,
            'room' => $tenant->room ? [
                'id' => $tenant->room->id,
                'room_number' => $tenant->room->room_number,
                'room_type' => $tenant->room->room_type,
                'price' => $tenant->room->price,
                'status' => $tenant->room->status,
            ] : null,
            'floor' => $tenant->room && $tenant->room->floor ? [
                'id' => $tenant->room->floor->id,
                'name' => $tenant->room->floor->name,
                'floor_number' => $tenant->room->floor->floor_number,
            ] : null,
            'property' => $tenant->room && $tenant->room->floor && $tenant->room->floor->property ? [
                'id' => $tenant->room->floor->property->id,
                'name' => $tenant->room->floor->property->name,
                'address' => $tenant->room->floor->property->address,
                'city' => $tenant->room->floor->property->city,
            ] : null
        ];

        return response()->json([
            'success' => true,
            'data' => $tenantData
        ]);
    }

    public function destroy(string $id)
    {
        try {
            $this->tenantRepository->delete($id);
            return ApiResponser::ok(['message' => 'Tenant deleted successfully']);
        } catch (Throwable $ex) {
            Log::error('Tenant deletion failed', ['error' => $ex]);
            return ApiResponser::serverError('Failed to delete tenant');
        }
    }
}
