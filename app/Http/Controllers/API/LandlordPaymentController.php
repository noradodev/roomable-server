<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponser;
use App\Models\LandlordPaymentConfiguration;
use App\Models\LandlordPaymentFile;
use App\Models\LandlordPaymentMethod;
use App\Models\LandlordPaymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class LandlordPaymentController extends Controller
{
    public function index()
    {
        try {
            $landlordId = Auth::id();

            $paymentMethods = LandlordPaymentMethod::with([
                'methodType',
                'configuration',
                'files' => function ($query) {
                    $query->where('file_type', 'qr_code');
                }
            ])
                ->where('landlord_id', $landlordId)
                ->where('is_active', true)
                ->get();

            $formattedMethods = $paymentMethods->map(function ($method) {
                $qrFile = $method->files->first();

                return [
                    'id' => $method->id,
                    'type' => $method->methodType->code,
                    'name' => $method->methodType->name,
                    'is_enabled' => $method->is_enabled,
                    'is_required' => $method->methodType->is_required,
                    'configuration' => $method->configuration ? [
                        'collector_name' => $method->configuration->collector_name,
                        'collection_location' => $method->configuration->collection_location,
                        'account_name' => $method->configuration->account_name,
                        'instructions' => $method->configuration->instructions,
                    ] : null,
                    'qr_image_url' => $qrFile ? $qrFile->file_url : null,
                    'created_at' => $method->created_at,
                    'updated_at' => $method->updated_at,
                ];
            });

            return ApiResponser::ok([
                'payment_methods' => $formattedMethods
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return ApiResponser::error('Failed to fetch payment methods', 500);
        }
    }

    public function saveSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cash.enabled' => 'required|boolean',
            'cash.collector_name' => 'required_if:cash.enabled,true|string|max:255',
            'cash.collection_location' => 'nullable|string|max:500',

            'qr.enabled' => 'required|boolean',
            'qr.name' => 'required_if:qr.enabled,true|string|max:255',
            'qr.qr_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            'qr.note' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return ApiResponser::error($validator->errors()->first());
        }

        try {
            DB::transaction(function () use ($request) {
                $landlordId = Auth::id();

                // Handle Cash Payment
                $cashType = LandlordPaymentType::where('code', 'cash')->first();
                $cashMethod = LandlordPaymentMethod::where('landlord_id', $landlordId)
                    ->where('payment_type_id', $cashType->id)
                    ->first();

                if ($cashMethod) {
                    $cashMethod->update(['is_enabled' => $request->input('cash.enabled')]);

                    if ($request->input('cash.enabled')) {
                        LandlordPaymentConfiguration::updateOrCreate(
                            ['payment_method_id' => $cashMethod->id],
                            [
                                'collector_name' => $request->input('cash.collector_name'),
                                'collection_location' => $request->input('cash.collection_location'),
                            ]
                        );
                    } else {
                        $cashMethod->configuration()->delete();
                    }
                }

                // Handle QR Payment
                $qrType = LandlordPaymentType::where('code', 'qr_code')->first();
                $qrMethod = LandlordPaymentMethod::where('landlord_id', $landlordId)
                    ->where('payment_type_id', $qrType->id)
                    ->first();

                if ($qrMethod) {
                    $isQrEnabled = $request->input('qr.enabled');
                    $qrMethod->update(['is_enabled' => $isQrEnabled]);

                    if ($isQrEnabled) {
                        LandlordPaymentConfiguration::updateOrCreate(
                            ['payment_method_id' => $qrMethod->id],
                            [
                                'account_name' => $request->input('qr.name'),
                                'instructions' => $request->input('qr.note'),
                            ]
                        );

                        if ($request->hasFile('qr.qr_image')) {
                            $this->handleQrCodeUpload($qrMethod, $request->file('qr.qr_image'));
                        }
                    } else {
                        $qrMethod->configuration()->delete();
                        $qrMethod->files()->delete();
                    }
                }
            });

            return ApiResponser::ok([
                'message' => 'Payment settings saved successfully!',
            ]);
        } catch (\Exception $e) {
            return ApiResponser::error('Failed to save payment settings: ' . $e->getMessage(), 500);
        }
    }

    // public function update(Request $request, $id)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'is_enabled' => 'required|boolean',
    //         'collector_name' => 'nullable|string|max:255',
    //         'collection_location' => 'nullable|string|max:500',
    //         'account_name' => 'nullable|string|max:255',
    //         'instructions' => 'nullable|string|max:1000',
    //         'qr_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
    //     ]);

    //     if ($validator->fails()) {
    //         return ApiResponser::error($validator->errors()->first(), 422);
    //     }

    //     try {
    //         $landlordId = Auth::id();
    //         $paymentMethod = LandlordPaymentMethod::where('id', $id)
    //             ->where('landlord_id', $landlordId)
    //             ->firstOrFail();

    //         DB::transaction(function () use ($request, $paymentMethod) {
    //             $paymentMethod->update(['is_enabled' => $request->input('is_enabled')]);

    //             if ($request->input('is_enabled')) {
    //                 $configData = [];

    //                 // Set appropriate fields based on payment method type
    //                 if ($paymentMethod->methodType->code === 'cash') {
    //                     $configData['collector_name'] = $request->input('collector_name');
    //                     $configData['collection_location'] = $request->input('collection_location');
    //                 } elseif ($paymentMethod->methodType->code === 'qr_code') {
    //                     $configData['account_name'] = $request->input('account_name');
    //                     $configData['instructions'] = $request->input('instructions');

    //                     if ($request->hasFile('qr_image')) {
    //                         $this->handleQrCodeUpload($paymentMethod, $request->file('qr_image'));
    //                     }
    //                 }

    //                 LandlordPaymentConfiguration::updateOrCreate(
    //                     ['payment_method_id' => $paymentMethod->id],
    //                     $configData
    //                 );
    //             } else {
    //                 $paymentMethod->configuration()->delete();
    //                 if ($paymentMethod->methodType->code === 'qr_code') {
    //                     $paymentMethod->files()->delete();
    //                 }
    //             }
    //         });

    //         return ApiResponser::ok([
    //             'message' => 'Payment method updated successfully!',
    //         ]);

    //     } catch (\Exception $e) {
    //         return ApiResponser::error('Failed to update payment method: ' . $e->getMessage(), 500);
    //     }
    // }

    public function getPaymentMethodTypes()
    {
        try {
            $types = LandlordPaymentType::where('is_active', true)
                ->orderBy('display_order')
                ->get(['id', 'code', 'name', 'is_required', 'display_order']);

            return ApiResponser::ok([
                'payment_method_types' => $types
            ]);
        } catch (\Exception $e) {
            Log::error($e);
            return ApiResponser::error('Failed to fetch payment method types', 500);
        }
    }

    private function handleQrCodeUpload(LandlordPaymentMethod $paymentMethod, $file)
    {
        $paymentMethod->files()->where('file_type', 'qr_code')->delete();

        $path = $file->store('qr-codes', 'public');

        LandlordPaymentFile::create([
            'landlord_payment_method_id' => $paymentMethod->id,
            'file_type' => 'qr_code',
            'original_name' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'file_url' => Storage::url($path),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }
}
