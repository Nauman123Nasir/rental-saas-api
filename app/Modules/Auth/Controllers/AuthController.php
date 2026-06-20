<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/login
     * Authenticate a user and return a JWT.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * POST /api/v1/auth/logout
     * Invalidate the current JWT.
     */
    public function logout(): JsonResponse
    {
        Auth::guard('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
        ]);
    }

    /**
     * POST /api/v1/auth/refresh
     * Refresh a JWT token.
     */
    public function refresh(): JsonResponse
    {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }

    /**
     * GET /api/v1/auth/me
     * Get the currently authenticated user's profile with roles, permissions, tenant and branch.
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->load(['roles.permissions', 'tenant', 'branch']);

        // Flatten permissions for the frontend
        $permissions = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = "{$permission->module}.{$permission->action}";
            }
        }
        $permissions = array_values(array_unique($permissions));

        return response()->json([
            'success' => true,
            'data'    => [
                'user'        => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'status'=> $user->status,
                ],
                'tenant'      => $user->tenant,
                'branch'      => $user->branch,
                'roles'       => $user->roles->pluck('name'),
                'permissions' => $permissions,
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/forgot-password
     * Generate password reset token and log the link.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $email = $request->email;
        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        $resetUrl = "http://localhost:4200/auth/reset-password?token={$token}&email=" . urlencode($email);

        // Log the reset URL
        logger("Password reset link for {$email}: {$resetUrl}");

        return response()->json([
            'success' => true,
            'message' => 'If your email is registered, you will receive a password reset link shortly (logged in laravel.log).',
        ]);
    }

    /**
     * POST /api/v1/auth/reset-password
     * Reset the user's password using the token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (! $record || ! Hash::check($request->token, $record->token)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token.',
            ], 422);
        }

        // Token expires in 60 minutes
        if (now()->subMinutes(60)->gt($record->created_at)) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'success' => false,
                'message' => 'Token has expired.',
            ], 422);
        }

        $user = \App\Models\User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
        ]);
    }

    /**
     * Build the token response payload.
     */
    private function respondWithToken(string $token): JsonResponse
    {
        return response()->json([
            'success'      => true,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
        ]);
    }
}
