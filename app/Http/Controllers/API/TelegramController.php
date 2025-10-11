<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TelegramLinkToken;
use App\Services\TelegramService;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function handle(Request $request)
    {
        $telegram = app(TelegramService::class);
        $update = $request->all();

        if (isset($update['message']['text'])) {
            $text = $update['message']['text'];
            $chatId = $update['message']['chat']['id'];
            $telegramId = $update['message']['from']['id'];
            $username = $update['message']['from']['username'] ?? null;

            if (str_starts_with($text, '/start ')) {
                $token = trim(substr($text, 7));

                $link = TelegramLinkToken::where('token', $token)->first();

                if ($link) {
                    $user = $link->user;
                    $user->profile()->updateOrCreate([], [
                        'telegram_id' => $telegramId,
                        'telegram_chat_id' => $chatId,
                        'telegram_username' => $username,
                    ]);

                    $link->delete();

                    $telegram->sendMessage($chatId, "✅ Your account has been successfully linked.");
                    Log::info('Telegram update:', $update);
                } else {
                    $telegram->sendMessage($chatId, "⚠️ Invalid or expired token.");
                }
            }
        }
        return response()->json(['ok' => true]);
    }

    public function generate(Request $request)
    {
        $user = $request->user();
        TelegramLinkToken::where('user_id', $user->id)->delete();

        $token = Str::random(32);

        TelegramLinkToken::create([
            'user_id' => $user->id,
            'token' => $token,
        ]);
        $deepLink = "https://t.me/roomable_test_bot?start={$token}";

        return response()->json(['link' => $deepLink]);
    }
}
