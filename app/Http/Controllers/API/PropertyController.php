<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StoreRequest;
use App\Http\Requests\Property\UpdateRequest;
use App\Http\Responses\ApiResponser;
use App\Interfaces\PropertyRepositoryInterface;
use App\Models\Property;
use App\Models\Tenant;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class PropertyController extends Controller
{
    private PropertyRepositoryInterface $propertyRepositoryInterface;

    public function __construct(PropertyRepositoryInterface $propertyRepositoryInterface)
    {
        $this->propertyRepositoryInterface = $propertyRepositoryInterface;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $properties = $this->propertyRepositoryInterface->forUser($user);
        return ApiResponser::ok(['properties' => $properties]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request)
    {
        $data = $request->validated();
        $userId = Auth::id();

        // Handle file upload
        if ($request->hasFile('props_image')) {
            $path = $request->file('props_image')->store('properties', 'public');
            $data['property']['image_url'] = $path;
        }

        try {
            $property = $this->propertyRepositoryInterface->createWithRelations($userId, $data);
            return ApiResponser::created([
                'message' => 'Property created successfully',
                'property' => $property,
            ]);
        } catch (\Throwable $ex) {
            Log::error('Failed to create property', [
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);
            return ApiResponser::serverError('Failed to create property');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($propertyId)
    {
        $user = Auth::user();
        $floors = $this->propertyRepositoryInterface->show($user, $propertyId);

        return ApiResponser::ok(['property' => $floors]);
    }

    public function listTenant()
    {
        $landlordId = Auth::id();

        $tenants = Tenant::where('landlord_id', $landlordId)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tenants,
        ]);
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Property $property)
    {
        $data = $request->validate([
            'landlord_id' => 'sometimes|uuid|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'image_url' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('props_image')) {
            $path = $request->file('props_image')->store('properties', 'public');
            $data['image_url'] = $path;
        }

        try {
            $this->propertyRepositoryInterface->update($data, $property->id);
            return ApiResponser::ok([]);
        } catch (Throwable $ex) {
            Log::error('Failed to create property', [
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
            ]);
            return ApiResponser::serverError('Failed to create property');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Property $property)
    {
        $this->propertyRepositoryInterface->delete($property->id);
        return ApiResponser::ok([]);
    }
}
