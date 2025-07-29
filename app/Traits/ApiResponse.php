<?php

namespace App\Traits;

trait ApiResponse
{
    public function successResponse($message = null, $data = null, $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message ?? __('messages.success'),
            'data' => $data,
        ], $code);
    }

    public function errorResponse($message = null, $errors = [], $code = 422)
    {
        return response()->json([
            'status' => true,
            'message' => $message ?? __('messages.error'),
            'errors' => $errors,
        ], $code);
    }

    public function notFoundResponse($message = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message ?? __('messages.not_found'),
        ], 404);
    }

    public function unauthorizedResponse($message = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message ?? __('messages.unauthorized'),
        ], 403);
    }

    public function forbiddenResponse($message = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message ?? __('messages.forbidden'),
        ], 403);
    }
    public function validationErrorResponse($errors, $message = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message ?? __('messages.validation_error'),
            'errors' => $errors,
        ], 422);
    }
    
}
