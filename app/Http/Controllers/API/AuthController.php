<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class AuthController extends Controller
{
    // REGISTER

    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'password' => 'required|min:6',
            'role' => 'required|in:ADMINISTRATOR,OPERATOR',
            'personnel_id' => 'nullable|exists:personnel,id'
        ]);

        $user = User::create([
            'username' => $request->username,
            'password' => $request->password,
            'role' => $request->role,
            'personnel_id' => $request->personnel_id,
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }

    // LOGIN

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $user = User::where(
            'username',
            $request->username
        )->first();

        if (
            !$user ||
            !Hash::check(
                $request->password,
                $user->password
            )
        ) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is disabled'
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user->load('personnel')
        ]);
    }

    // LOGOUT

    public function logout(Request $request)
    {
        $request
            ->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    // CURRENT USER

    public function me(Request $request)
    {
        return response()->json(
            $request->user()->load('personnel')
        );
    }


    public function changePassword(Request $request)
{

    Log::info('AUTH USER', [
    'user' => $request->user()
]);
    $request->validate([

        'current_password' => 'required',

        'new_password' => 'required|min:8',

        'new_password_confirmation' => 'required'

    ]);

    if (
        $request->new_password !==
        $request->new_password_confirmation
    ) {

        return response()->json([
            'message' => 'Passwords do not match'
        ], 422);

    }

    $user = $request->user();

    if (
        !Hash::check(
            $request->current_password,
            $user->password
        )
    ) {

        return response()->json([
            'message' => 'Current password is incorrect'
        ], 422);

    }

    $user->update([
        'password' => Hash::make(
            $request->new_password
        )
    ]);

    return response()->json([
        'message' => 'Password changed successfully'
    ]);
}
}