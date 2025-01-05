<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validator Error.', $validator->errors(), 400);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = auth()->guard('api')->attempt($credentials)) {
            return $this->sendError('Wrong email or password', ['error' => 'Unauthorized'], 401);
        }

        $success = $this->respondWithToken($token);

        return $this->sendResponse($success, 'Login successfully.');
    }

    protected function respondWithToken($token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'user' => auth()->guard('api')->user(),
            'expires_in' => $this->convertExpiredToken(auth()->guard('api')->factory()->getTTL() * 60)
        ];
    }

    protected function convertExpiredToken(int $seconds): string
    {
        if ($seconds >= 3600) {
            // If it's more than or equal to an hour
            $hours = floor($seconds / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        } elseif ($seconds >= 60) {
            // If it's more than or equal to a minute
            $minutes = floor($seconds / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        } else {
            // If it's less than a minute
            return $seconds . ' second' . ($seconds > 1 ? 's' : '');
        }
    }
}
