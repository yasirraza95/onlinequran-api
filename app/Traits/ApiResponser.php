<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use App\Models\Log;

trait ApiResponser
{
    // FIXME
    protected function loginResponse($request, $user, $token, $code = 200)
    {
        $response = [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
        ];

        // 'endpoint' => Route::getCurrentRoute()->getPath(),
        $insertData = [
            'request_type' => $request->method(),
            'ip_address' => $request->ip(),
            'endpoint' => $request->url(),
            'referer_link' => request()->headers->get('Referer'),
            'browser' => $request->header('User-Agent'),
            'form_data' => json_encode($request->all()),
            'response' => json_encode($response),
            'http_status' => $code,
        ];
        Log::create($insertData);

        return response()->json(
            [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60,
            ],
            $code
        );
    }

    // FIXME
    protected function successResponse($request, $data, $code = 200)
    {
        $insertData = [
            'request_type' => $request->method(),
            'ip_address' => $request->ip(),
            'endpoint' => $request->url(),
            'referer_link' => request()->headers->get('Referer'),
            'browser' => $request->header('User-Agent'),
            'authorization' => $request->header('Authorization'),
            'form_data' => json_encode($request->all()),
            'response' => json_encode($data),
            'http_status' => $code,
        ];
        Log::create($insertData);

        return response()->json($data, $code);
    }

    // FIXME
    protected function exportResponse($request, $data, $code = 200)
    {
        $insertData = [
            'request_type' => $request->method(),
            'ip_address' => $request->ip(),
            'endpoint' => $request->url(),
            'referer_link' => request()->headers->get('Referer'),
            'browser' => $request->header('User-Agent'),
            'authorization' => $request->header('Authorization'),
            'form_data' => json_encode($request->all()),
            'response' => json_encode($data),
            'http_status' => $code,
        ];
        Log::create($insertData);

        return response()->json($data, $code);
    }

    // FIXME
    protected function errorResponse($request, $message, $code)
    {
        $insertData = [
            'request_type' => $request->method(),
            'ip_address' => $request->ip(),
            'endpoint' => $request->url(),
            'referer_link' => request()->headers->get('Referer'),
            'browser' => $request->header('User-Agent'),
            'authorization' => $request->header('Authorization'),
            'form_data' => json_encode($request->all()),
            'response' => json_encode($message),
            'http_status' => $code,
        ];
        Log::create($insertData);

        return response()->json($message, $code);
    }
}