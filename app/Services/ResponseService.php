<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class ResponseService
{
    /**
     * Sends a  JSON response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  array | string  $error
     * @param  int  $status
     * @param  array  $headers
     * @param  int  $options
     * @return \Illuminate\Http\JsonResponse
     */
    static function json(
        $data = null, 
        $message = null, 
        $error = null, 
        $status = 200, 
        $headers = [], 
        $options = 0) {
        return response()->json([
            'data' => $data,
            'message' => $message ? $message : ($error ? 'There was an error with the request' : 'Request successful'),
            'error' => $error
        ], $status, $headers, $options);
    }

    /**
     * standardize service response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  array  $errors
     * @param  string  $status
     * @return array
     */
    public function formatServiceResponse(string $status, string $message, array $errors, mixed $data) {
        return [
            'status' => $status,
            'message' => $message,
            'errors' => $errors,
            'data' => $data
        ];
    }
}
