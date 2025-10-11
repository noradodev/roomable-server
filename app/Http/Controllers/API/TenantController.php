<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreRequest;
use App\Http\Requests\Tenant\UpdateRequest;
use App\Http\Responses\ApiResponser;
use App\Interfaces\TenantRepositoryInterface;
use App\Models\Tenant;
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

    public function index()
    {
        $user = Auth::user();
        $tenants = $this->tenantRepository->index($user);
        return ApiResponser::ok(['tenants' => $tenants]);
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
