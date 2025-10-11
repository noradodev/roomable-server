<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponser;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request): ApiResponser
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email:rfc,dns|unique:users,email',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->profile()->create([
                'phone' => null,
                'address' => null,
            ]);
            $user->assignRole('landlord');
            $token = $user->createToken('auth_token')->accessToken;

            return ApiResponser::ok([
                'user' => $user->load('profile'),
                'token' => $token,
            ]);;
        } catch (Exception $e) {
            return ApiResponser::error($e);
        }
    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return ApiResponser::error('Invalid credentials', 401);
        }

        $token = $user->createToken('auth_token')->accessToken;

        return ApiResponser::ok([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames()->first(),
                'profile' => $user->profile,
            ],
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        try {
            $user = $request->user();

            $user->load('profile');

            return ApiResponser::ok(['curr_user' => $user]);
        } catch (Exception $e) {
            return ApiResponser::error($e);
        }
    }
}
