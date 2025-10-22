<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
    }

    public function sendMessage($chatId, $text, $options = [])
    {
        $payload = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options);
        $response = Http::asJson()->post($this->apiUrl('sendMessage'), $payload);
        if ($response->failed()) {
            Log::error('Telegram send failed', [
                'chat_id' => $chatId,
                'response' => $response->body(),
            ]);
        }
        return $response;
    }
    public function answerCallbackQuery($callbackQueryId, $text = 'Option chosen.')
    {
        return Http::post($this->apiUrl('answerCallbackQuery'), [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => false
        ]);
    }

    public function editMessageText($chatId, $messageId, $text, array $options = [])
    {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $options['parse_mode'] ?? 'HTML',
        ];

        if (isset($options['reply_markup'])) {
            $payload['reply_markup'] = $options['reply_markup'];
        }

        $response = Http::post($this->apiUrl('editMessageText'), $payload);

        if ($response->failed()) {
            Log::error('Telegram editMessageText failed', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'response' => $response->body(),
            ]);
        }

        return $response;
    }
    protected function apiUrl($method)
    {
        return "https://api.telegram.org/bot{$this->botToken}/{$method}";
    }
}
