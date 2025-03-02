<?php

namespace App\Classes;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * dynamic response method
     *
     * @param string $status
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param array $additionalData
     * @param string $dataKey
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private static function response(string $status, $data = null, string $message = null, int $statusCode = 200, array $additionalData = [], string $dataKey = 'data'): JsonResponse
    {
        $response = array_merge([
            'status' => $status,
            $dataKey => $data,
            'message' => $message
        ], $additionalData);

        return response()->json($response, $statusCode);
    }

    /**
     * 200 OK
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withOk(string $message = null, $data = null): JsonResponse
    {
        return self::response('OK', $data, $message, 200);
    }

    /**
     * 201 Created
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withCreated(string $message = null, $data = null): JsonResponse
    {
        return self::response('Created', $data, $message, 201);
    }

    /**
     * 204 No Content
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withNoContent(string $message = null, $data = null): JsonResponse
    {
        return self::response('No Content', $data, $message, 204);
    }

    /**
     * 400 Bad Request
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withBadRequest(string $message = null, $data = null): JsonResponse
    {
        return self::response('Bad Request', $data, $message, 400);
    }

    /**
     * 401 Unauthorized
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withUnauthorized(string $message = null, $data = null): JsonResponse
    {
        return self::response('Unauthorized', $data, $message, 401);
    }

    /**
     * 403 Forbidden
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withForbidden(string $message = null, $data = null): JsonResponse
    {
        return self::response('Forbidden', $data, $message, 403);
    }

    /**
     * 404 Not Found
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withNotFound(string $message = null, $data = null): JsonResponse
    {
        return self::response('Not Found', $data, $message, 404);
    }

    /**
     * 406 Not Acceptable
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withNotAcceptable(string $message = null, $data = null): JsonResponse
    {
        return self::response('Not Acceptable', $data, $message, 406);
    }

    /**
     * 409 Conflict
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withConflict(string $message = null, $data = null): JsonResponse
    {
        return self::response('Conflict', $data, $message, 409);
    }

    /**
     * 422 Unprocessable Entity
     *
     * @param string $message
     * @param mixed $errors
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withUnprocessableContent(string $message = null, $errors = []): JsonResponse
    {
        return self::response('Unprocessable Content', $errors, $message, 422, [], 'errors');
    }

    /**
     * 500 Internal Server Error
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withInternalServerError(string $message = null, $data = null): JsonResponse
    {
        return self::response('Internal Server Error', $data, $message, 500);
    }

    /**
     * 503 Service Unavailable
     *
     * @param string $message
     * @param mixed $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function withServiceUnavailable(string $message = null, $data = null): JsonResponse
    {
        return self::response('Service Unavailable', $data, $message, 503);
    }
}
