<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class TelegramService
{
    protected $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
    }

    public function sendMessage($chatId, $text, $options = [])
    {
        return Http::post($this->apiUrl('sendMessage'), array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $options));
    }
    protected function apiUrl($method)
    {
        return "https://api.telegram.org/bot{$this->botToken}/{$method}";
    }
}
