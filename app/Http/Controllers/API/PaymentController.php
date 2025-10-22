<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentCollection;
use App\Http\Responses\ApiResponser;
use App\Jobs\SendPaymentNotifications;
use App\Jobs\SendRejectedMessage;
use App\Models\LandlordPaymentMethod;
use App\Models\Payment;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $landlordId = Auth::user()->id;

        $query = Payment::query()
            ->whereHas('tenant.room.floor.property.user', function ($q) use ($landlordId) {
                $q->where('id', $landlordId);
            })
            ->with(['tenant', 'tenant.room'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('month')) {
            $query->where('month_years', $request->month);
        }

        if ($request->filled('tenant_name')) {
            $query->whereHas('tenant', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->tenant_name . '%');
            });
        }

        $payments = $query->paginate($request->get('per_page', 10));
        $structuredData = (new PaymentCollection($payments))->toArray($request);

 return ApiResponser::ok(
            data: $structuredData,
            successMessage: 'Tenants retrieved successfully'
        );
    }

    private function getTenantPaymentKeyboard(Payment $payment): array
    {
        $landlordId = $payment->tenant->room->floor->property->landlord_id;

        $methods = LandlordPaymentMethod::where('landlord_id', $landlordId)
            ->where('is_enabled', true)
            ->with('methodType')
            ->get();

        $keyboard = [];

        foreach ($methods as $method) {
            $tenantAppUrl = config('app.front_url');

            $url = $tenantAppUrl . "/payments/{$payment->id}/method/{$method->id}";

            $keyboard[] = [
                ['text' => $method->methodType->name, 'url' => $url],
            ];
        }

        return [
            'inline_keyboard' => $keyboard,
        ];
    }

    public function update(Request $request, $id, TelegramService $telegram)
    {
        $payment = Payment::with('tenant.room.floor.property.user.profile')->findOrFail($id);
        $user = Auth::user();

        if ($payment->tenant->room->floor->property->landlord_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized — you do not own this property.'
            ], 403);
        }

        $validated = $request->validate([
            'electricity_cost' => 'nullable|numeric|min:0',
            'water_cost' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $payment->fill($validated);

        $payment->total_amount = $payment->amount + ($payment->electricity_cost ?? 0) + ($payment->water_cost ?? 0);

        $payment->status = 'awaiting_tenant';
        $payment->save();
        $landlordChatId = $payment->tenant->room->floor->property->user->profile->telegram_chat_id ?? null;
        if ($landlordChatId && $payment->telegram_message_id) {
            $newText = "✅ <b>Utilities Added</b>\n\n"
                . "👤 <b>Tenant:</b> {$payment->tenant->name}\n"
                . "🏠 <b>Room:</b> {$payment->tenant->room->room_number}\n"
                . "💵 <b>Total:</b> $" . number_format($payment->total_amount, 2) . "\n"
                . "🗓 <b>Month:</b> {$payment->month_years}\n\n"
                . "📢 Payment details updated and tenant has been notified.";

            $telegram->editMessageText(
                $landlordChatId,
                $payment->telegram_message_id,
                $newText,
                [
                    'parse_mode' => 'HTML',
                    'reply_markup' => ['inline_keyboard' => []],
                ]
            );
        }

        $tenant = $payment->tenant;
        if ($tenant->telegram_chat_id) {
            $tenantKeyboard = $this->getTenantPaymentKeyboard($payment);
            $message = "📢 Rent bill ready!\n" .
                "🏠 Room: {$tenant->room->room_number}\n" .
                "💰 Base Rent: \${$payment->amount}\n" .
                "💧 Water: \${$payment->water_cost}\n" .
                "⚡ Electricity: \${$payment->electricity_cost}\n" .
                "📅 Month: {$payment->month_years}\n" .
                "----------------------------------\n" .
                " Total Amount: \${$payment->total_amount}\n" .
                "----------------------------------\n\n" .
                "➡️ Please select a payment method below:";
            $telegram->sendMessage(
                $tenant->telegram_chat_id,
                $message,
                [
                    'parse_mode' => 'HTML',
                    'reply_markup' => $tenantKeyboard
                ]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Payment updated and tenant notified.',
            'data' => $payment
        ]);
    }

    public function markPaid(Request $request, $id)
    {
        $payment = Payment::with('tenant.room.floor.property.user')->findOrFail($id);
        $user = Auth::user();
        $validated = $request->validate([
            'method' => "sometimes|in:cash,bank,qr,other"
        ]);;


        if ($payment->tenant->room->floor->property->landlord_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized — not your tenant.'
            ], 403);
        }

        $payment->status = 'paid';
        $payment->method = $validated['method'];
        $payment->paid_at = now();
        $payment->save();

        dispatch(new  SendRejectedMessage((int) $payment->id));

        return response()->json([
            'status' => 'success',
            'message' => 'Payment marked as paid and notifications sent.',
            'data' => $payment
        ]);
    }
    public function rejectPayment(Request $request, $id)
{
    $payment = Payment::with('tenant.room.floor.property.user')->findOrFail($id);
    $user = Auth::user();

    if ($payment->tenant->room->floor->property->landlord_id !== $user->id) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
    }
    
    $validated = $request->validate(['rejection_reason' => 'required|string|max:500']);

    $payment->status = 'awaiting_tenant'; 
    $payment->rejection_reason = $validated['rejection_reason'];
    $payment->save();

    // $payment->clearProofOfPayment(); 

    // Dispatch notification to tenant
            dispatch(new SendRejectedMessage((int)$payment->id));


    return response()->json([
        'status' => 'success',
        'message' => 'Payment rejected. Status reverted to Awaiting Tenant.',
        'data' => $payment
    ]);
}

public function show(string $id)
{
    $user = Auth::user();

    $payment = Payment::with(['tenant.landlord', 'room'])->findOrFail($id);

    $landlordId = $payment->tenant?->landlord_id;

    if (!$landlordId || $landlordId !== $user->id) {
        return ApiResponser::error(
            'Unauthorized — You do not own this property or the payment details are incomplete.', 
            403
        );
    }
    
    return ApiResponser::ok(["payment" => $payment]);
}
}
