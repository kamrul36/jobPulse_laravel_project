<?php

namespace App\Helper;

use Illuminate\Http\JsonResponse;


class ResponseHelper
{
    public static function respond(
        string $version,
        string $msg,
        string $method,
        int $code,
         $data = null,
        array $pagination = null
    ): JsonResponse {
        $response = [
            'success' => $code >= 200 && $code < 300,
            'version' => $version,
            'operation' => $msg,
            'method' => $method,
        ];

        // Only include data if it's provided
        if (!is_null($data)) {
            $response['data'] = $data;
        }

        if ($pagination) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $code);
    }
}