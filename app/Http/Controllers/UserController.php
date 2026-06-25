<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        return User::select(
            'id',
            'username',
            'role',
            'is_active',
            'personnel_id',
            'created_at'
        )
        ->orderBy('username')
        ->get();
    }

    public function store(Request $request)
    {

    $request->validate([
    'username' => 'required|unique:users',
    'password' => [
        'required',
        'min:8',
        'regex:/[a-z]/',
        'regex:/[A-Z]/',
        'regex:/[0-9]/',
        'regex:/[@$!%*?&]/'
    ],
    'role' => 'required'
]);

        $user = User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

public function update(Request $request, User $user)
{
    $validated = $request->validate([
        'username' => 'required|unique:users,username,' . $user->id,
        'role' => 'required|in:ADMINISTRATOR,OPERATOR'
    ]);

    // Zuia user kubadilisha role yake mwenyewe
    if (
        auth()->id() === $user->id &&
        $validated['role'] !== $user->role
    ) {

        return response()->json([
            'message' => 'You cannot change your own role.'
        ], 422);

    }

    if ($request->filled('password')) {

        $request->validate([
            'password' => [
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&]/'
            ]
        ]);

        $user->password = Hash::make($request->password);
    }

    $user->username = $validated['username'];
    $user->role = $validated['role'];

    $user->save();

    return response()->json([
        'message' => 'User updated successfully'
    ]);
}

public function toggleStatus(User $user)
{
    if ($user->id === Auth::id()) {

        return response()->json([
            'message' =>
                'You cannot deactivate your own account'
        ], 422);

    }

    $user->is_active = !$user->is_active;

    $user->save();

    return response()->json([
        'message' => 'User status updated',
        'is_active' => $user->is_active
    ]);
}
}