<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponser;
use App\Models\TelegramLinkToken;
use App\Models\Tenant;
use App\Models\TenantTelegramLinkToken;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{

    protected $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    public function handle(Request $request)
    {
        //      if ($request->header('X-Webhook-Token') !== env('TELEGRAM_BOT_TOKEN')) {
        //     Log::warning('Invalid Telegram webhook signature', [
        //         'received' => $request->header('X-Webhook-Token')
        //     ]);
        //     abort(403, 'Invalid webhook signature');
        // }

        $telegram = app(TelegramService::class);
        $update = $request->all();

        if (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
            return response()->json(['ok' => true]);
        }

        if (!isset($update['message']['text'])) {
            return response()->json(['ok' => true]);
        }

        $text = trim($update['message']['text']);
        $chatId = $update['message']['chat']['id'];
        $telegramId = $update['message']['from']['id'];
        $username = $update['message']['from']['username'] ?? null;

        if (!Str::startsWith($text, '/start ')) {
            return response()->json(['ok' => true]);
        }

        $token = trim(substr($text, 7));

        if (Str::startsWith($token, 'tnt_')) {
            $tenantLink = \App\Models\TenantTelegramLinkToken::where('token', $token)->first();

            if (!$tenantLink) {
                $telegram->sendMessage($chatId, "⚠️ Invalid or expired tenant token.");
                return response()->json(['ok' => true]);
            }

            $tenant = \App\Models\Tenant::find($tenantLink->tenant_id);
            if (!$tenant) {
                $telegram->sendMessage($chatId, "⚠️ Tenant not found.");
                return response()->json(['ok' => true]);
            }

            $tenant->update([
                'telegram_id' => $telegramId,
                'telegram_chat_id' => $chatId,
                'telegram_username' => $username,
            ]);

            $tenantLink->delete();
            $telegram->sendMessage($chatId, "✅ Tenant account successfully linked.");
            Log::info('Telegram tenant linked', ['tenant_id' => $tenant->id, 'chat_id' => $chatId]);
            return response()->json(['ok' => true]);
        }

        $link = \App\Models\TelegramLinkToken::where('token', $token)->first();

        if (!$link) {
            $telegram->sendMessage($chatId, "⚠️ Invalid or expired user token.");
            return response()->json(['ok' => true]);
        }

        $user = $link->user;
        $user->profile()->updateOrCreate([], [
            'telegram_id' => $telegramId,
            'telegram_chat_id' => $chatId,
            'telegram_username' => $username,
        ]);

        $link->delete();
        $telegram->sendMessage($chatId, "✅ Your account has been successfully linked.");
        Log::info('Telegram user linked', ['user_id' => $user->id, 'chat_id' => $chatId]);

        return response()->json(['ok' => true]);
    }

    public function generate(Request $request)
    {
        $user = $request->user();
        $activeToken = TelegramLinkToken::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->first();

        if ($activeToken) {
            $token = $activeToken->token;
            $expiresAt = $activeToken->expires_at;
        } else {
            $token = Str::random(32);
            $expiresAt = Carbon::now()->addMinutes(5);

            TelegramLinkToken::create([
                'user_id' => $user->id,
                'token' => $token,
                'expires_at' => $expiresAt,
            ]);
        }

        if (!$activeToken) {
            TelegramLinkToken::where('user_id', $user->id)
                ->where('expires_at', '<=', now())
                ->delete();
        }

        $deepLink = "https://t.me/roomable_bot?start={$token}";

        return ApiResponser::created([
            'link' => $deepLink,
            'token' => $token,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }
    public function generateTenantLink($tenantId)
    {
        $exist = Tenant::where('id', $tenantId)->firstOrFail();
        $token = 'tnt_' . Str::random(32);

        if ($exist) {
            TenantTelegramLinkToken::where('tenant_id', $tenantId)->delete();
            TenantTelegramLinkToken::create([
                'tenant_id' => $exist->id,
                'token' => $token,
            ]);
            $deepLink = "https://t.me/roomable_bot?start={$token}";
            return ApiResponser::created([
                'link' => $deepLink,
                'token' => $token,
            ]);
        }
    }


    protected function handleCallbackQuery($callbackQuery)
    {

        $callbackData = $callbackQuery['data'];
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];

        // CRITICAL: Acknowledge the click immediately. This stops the spinning icon.
        $this->telegram->answerCallbackQuery($callbackQuery['id']);

        // --- SAFE PARSING LOGIC ---
        $parts = explode(':', $callbackData);

        // Check if the expected parts exist (at least action and method)
        if (count($parts) < 2 || $parts[0] !== 'pay_method') {
            Log::warning('Invalid callback data received.', ['data' => $callbackData]);
            return; // Stop processing invalid data
        }

        $action = $parts[0]; // 'pay_method'
        $method = $parts[1]; // 'cash' or 'qr'

        // --- EXECUTION ---
        if ($action === 'pay_method') {
            try {
                $message = $this->getPaymentInstructions($method);

                // This is correctly using Markdown and the options array
                $this->telegram->editMessageText($chatId, $messageId, $message, [
                    'parse_mode' => 'Markdown'
                ]);
            } catch (\Throwable $e) {
                // Log any unexpected error during message editing/fetching instructions
                Log::error("Failed to edit Telegram message after callback.", [
                    'error' => $e->getMessage(),
                    'callback' => $callbackData
                ]);
            }
        }
    }
    protected function getPaymentInstructions(string $method): string
    {
        if ($method === 'cash') {
            return "✅ You chose **Pay by Cash**. Please contact John at 012-345-678 to arrange the handover.";
        }
        if ($method === 'qr') {
            return "✅ You chose **Pay by QR Code**. Please scan the QR code for [Bank Name] and reply with the transaction receipt once done.  
                **Account:** 123456789
                **Name:** Landlord Name";
        }
        return "Unknown payment method selected.";
    }
}
