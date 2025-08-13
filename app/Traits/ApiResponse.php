<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Success response
     */
    protected function success($data = null, string $message = 'Request successful', int $code = 200)
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Error response
     */
    protected function error(string $message = 'An error occurred', int $code = 400, $errors = null)
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $code);
    }

    /**
     * Validation error response
     */
    protected function validationError($errors, string $message = 'Validation failed')
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], 422);
    }

    /**
     * Not found response
     */
    protected function notFound(string $message = 'Resource not found')
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null
        ], 404);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized')
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null
        ], 401);
    }

    /**
     * Forbidden response
     */
    protected function forbidden(string $message = 'Forbidden')
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null
        ], 403);
    }

    /**
     * Created response
     */
    protected function created($data = null, string $message = 'Resource created successfully')
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], 201);
    }

    /**
     * No content response
     */
    protected function noContent(string $message = 'Request successful')
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => null
        ], 204);
    }
}