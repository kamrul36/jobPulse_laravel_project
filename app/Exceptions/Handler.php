<?php

use App\Helper\ResponseHelper;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;

class Handler extends ExceptionHandler
{

    public function render($request, Throwable $exception)
    {
        // Validation errors
        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $exception->errors(),
            ], 422);
        }

        // Route / Model not found
        if ($exception instanceof NotFoundHttpException) {
            return ResponseHelper::respond(
                'v1',
                'Resource not found',
                $request->method(),
                404
            );
        }

        // Unauthenticated
        if ($exception instanceof AuthenticationException) {
            return ResponseHelper::respond(
                'v1',
                'Unauthenticated',
                $request->method(),
                401
            );
        }

        // Default server error
        return ResponseHelper::respond(
            'v1',
            config('app.debug') ? $exception->getMessage() : 'Server Error',
            $request->method(),
            500
        );
    }

}
