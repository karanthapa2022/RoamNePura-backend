<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\Rule;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.',
            ], 401);
        }

        if (!$user->is_admin) {
            return response()->json([
                'message' => 'You are not authorized to access the admin panel.',
            ], 403);
        }

        $newToken = $user->createToken('admin-token');

$newToken->accessToken->forceFill([
    'last_activity_at' => now(),
])->save();

$token = $newToken->plainTextToken;

        return response()->json([
            'message' => 'Admin login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
    public function logout(Request $request)
{
    $request->user()->currentAccessToken()?->delete();

    return response()->json([
        'message' => 'Admin logout successful.',
    ]);
}
public function me(Request $request)
{
    return response()->json([
        'user' => [
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
        ],
    ]);
}

public function changePassword(Request $request)
{
    $validated = $request->validate([
        'current_password' => ['required', 'string'],
        'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
    ]);

    $user = $request->user();

    if (!Hash::check($validated['current_password'], $user->password)) {
        return response()->json([
            'message' => 'Your current password is incorrect.',
        ], 422);
    }

    $user->update([
        'password' => Hash::make($validated['password']),
    ]);

    return response()->json([
        'message' => 'Your password has been changed successfully.',
    ]);
}
public function updateProfile(Request $request)
{
    $user = $request->user();

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($user->id),
        ],
    ]);

    $user->update($validated);

    return response()->json([
        'message' => 'Admin profile updated successfully.',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
    ]);
}

public function updateActivity(Request $request)
{
    $token = $request->user()->currentAccessToken();

    $token->forceFill([
        'last_activity_at' => now(),
    ])->save();

    return response()->json([
        'message' => 'Admin activity updated.',
    ]);
}
}

