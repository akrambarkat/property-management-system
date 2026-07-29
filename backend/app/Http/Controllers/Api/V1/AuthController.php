<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = $request->input('identifier', $request->input('phone'));

        $userQuery = User::query();

        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $userQuery->where('email', $identifier);
        } else {
            $userQuery->where('phone', $identifier);
        }

        $user = $userQuery->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'رقم الهاتف أو البريد الإلكتروني أو كلمة المرور غير صحيحة',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'هذا الحساب غير مفعّل',
            ], 403);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'data' => [
                'token' => $token,
                'user' => $user->only([
                    'id',
                    'name',
                    'email',
                    'phone',
                    'role',
                    'preferred_currency',
                    'is_active',
                ]),
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    public function user(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => auth()->user()->only([
                'id',
                'name',
                'email',
                'phone',
                'role',
                'preferred_currency',
                'is_active',
            ]),
        ]);
    }
}
