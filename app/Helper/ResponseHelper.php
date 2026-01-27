<?php

namespace App\Helper;

use Illuminate\Http\JsonResponse;


class ResponseHelper
{
    public static function Out($version, $msg, $method,  $data, $code): JsonResponse
    {
        return response()->json([ 'version'=> $version , 'message' => $msg, 'method' => $method, 'data' => $data], $code);
    }
}