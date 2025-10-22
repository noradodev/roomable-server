<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponser;
use App\Models\Payment;
use App\Models\LandlordPaymentMethod;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TenantPaymentController extends Controller
{


    public function showPaymentMethod(Payment $payment, LandlordPaymentMethod $method)
    {
        if ($payment->status !== 'awaiting_tenant') {
            return ApiResponser::error('This payment is not ready for submission or has already been completed.', 400);
        }

        $method->load(['methodType', 'configuration', 'files']); 

        if ($method->landlord_id !== $payment->tenant->room->floor->property->landlord_id) {
             return ApiResponser::error('Payment method is invalid for this payment.', 404);
        }

        $responseData = [
            'payment' => [
                'id' => $payment->id,
                'total_amount' => (float) $payment->total_amount,
                'month' => $payment->month_years,
                'room_number' => $payment->tenant->room->room_number ?? 'N/A',
            ],
            'method' => [
                'id' => $method->id,
                'type_name' => $method->methodType->name,
            ],
            'config' => [],
        ];
        
        if ($method->configuration) {
            $responseData['config'] = [
                'instructions' => $method->configuration->instructions,
                'collector_name' => $method->configuration->collector_name, 
                'collection_location' => $method->configuration->collection_location,
                'account_name' => $method->configuration->account_name,
                'account_number' => $method->configuration->account_number,
                'files' => $method->files->map(fn($file) => ['type' => $file->file_type, 'url' => $file->file_url])->toArray(),
            ];
        }

        return ApiResponser::ok($responseData);
    }

    /**
     * Step 2: Tenant submits payment confirmation (for Cash or other methods).
     * Endpoint: POST /api/tenant/payments/{payment}/submit
     */
   public function submitPayment(Request $request, Payment $payment)
    {
        // 1. Fetch the chosen method and its type
        $method = LandlordPaymentMethod::with('methodType')->findOrFail($request->method_id);
        $paymentTypeSlug = strtolower($method->methodType->name); // Use methodType for the relationship name
        
        $rules = [
            'method_id' => [
                'required',
                'string',
                Rule::exists('landlord_payment_methods', 'id')->where(function ($query) use ($payment) {
                    return $query->where('landlord_id', $payment->tenant->room->floor->property->landlord_id);
                }),
            ],
            'note' => 'nullable|string|max:500', 
        ];

        $proofUrl = null;
        
        if ($paymentTypeSlug === 'qr code' || $paymentTypeSlug === 'bank transfer') {
            $rules['proof_file'] = 'required|file|image|max:5000'; // Enforce file upload for proof
        } else {
            // Cash and other non-digital methods do not require a file
            $rules['proof_file'] = 'nullable';
        }

        // 4. Perform Validation
        $validated = $request->validate($rules);
        
        // Ensure payment status is correct
        if ($payment->status !== 'awaiting_tenant') {
            return ApiResponser::error('Payment cannot be submitted in its current state.', 400);
        }

        // 5. Handle File Upload (Only if present and required)
        if ($request->hasFile('proof_file')) {
            $path = $request->file('proof_file')->store('payment_proofs', 'public');
            $proofUrl = Storage::url($path);
        }

        $payment->update([
            'landlord_payment_method_id' => $validated['method_id'],
            'note' => $validated['note'] ?? null,
            'proof_url' => $proofUrl,
            'paid_at' => now(), 
            'status' => 'awaiting_confirmation', 
        ]);

        $this->notifyLandlordOfTenantSubmission($payment);

        return ApiResponser::ok(['message' => 'Payment submitted for confirmation.']);
    }
    
    /**
     * Helper to notify landlord via Telegram that a payment needs manual confirmation.
     */
    private function notifyLandlordOfTenantSubmission(Payment $payment)
    {
        $payment->load('tenant.room.floor.property.user.profile');
        $method = LandlordPaymentMethod::with('methodType')->find($payment->landlord_payment_method_id);
        
        $landlordChatId = $payment->tenant->room->floor->property->user->profile->telegram_chat_id ?? null;

        if ($landlordChatId) {
            $telegram = app(TelegramService::class);
            $message = "🔔 <b>Action Required: Payment Submitted</b>\n\n"
                . "👤 <b>Tenant:</b> {$payment->tenant->name}\n"
                . "🏠 <b>Room:</b> {$payment->tenant->room->room_number}\n"
                . "💵 <b>Total Amount:</b> $" . number_format($payment->total_amount, 2) . "\n"
                . "💳 <b>Method Claimed:</b> " . ($method->methodType->name ?? 'N/A') . "\n"
                . "🗓 <b>Month:</b> {$payment->month_years}\n\n"
                . "The tenant has claimed payment. Please review the details and confirm receipt."
                . "\n\n👉 Review and Confirm: " . config('app.front_url') . "/payments/view/{$payment->id}";

            $telegram->sendMessage($landlordChatId, $message, ['parse_mode' => 'HTML']);
        }
    }
}