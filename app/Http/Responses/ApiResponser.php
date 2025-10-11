<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApiResponser implements Responsable
{
    protected int $httpCode;
    protected mixed $data;
    protected string $errorMessage;

    public function __construct(int $httpCode, mixed $data = [], string $errorMessage = '')
    {

        if (! (($httpCode >= 200 && $httpCode <= 300) || ($httpCode >= 400 && $httpCode <= 600))) {
            throw new \RuntimeException($httpCode . ' is not valid');
        }

        $this->httpCode = $httpCode;
        $this->data = $data;
        $this->errorMessage = $errorMessage;
    }

    public function toResponse($request): \Illuminate\Http\JsonResponse
    {
        $success = $this->httpCode >= 200 && $this->httpCode < 300;

        $payload = [
            'success' => $success,
            'status_code' => $this->httpCode,
        ];

        if ($success) {
            $payload['data'] = $this->data;
        } else {
            Log::info($this->errorMessage);
            $payload['error'] = [
                'message' => $this->errorMessage ?: 'Unknown error',
            ];
        }

        return response()->json(
            data: $payload,
            status: $this->httpCode,
            options: JSON_UNESCAPED_UNICODE
        );
    }

    public static function ok(array $data)
    {
        return new static(200, $data);
    }
    public static function created(array $data)
    {
        return new static(201, $data);
    }

    public static function notFound(string $errorMessage = "Item not found")
    {
        return new static(404, errorMessage: $errorMessage);
    }
    public static function error(string $message, int $code = 400, array $data = [])
    {
        return new static($code, $data, $message);
    }

    public static function unauthorized(string $message = 'Unauthorized')
    {
        return new static(401, errorMessage: $message);
    }

    public static function forbidden(string $message = 'Forbidden')
    {
        return new static(403, errorMessage: $message);
    }

    public static function serverError(string $message = 'Server error')
    {
        return new static(500, errorMessage: $message);
    }
}
