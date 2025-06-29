<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);

        // Generate a new API token for the authenticated user
        return ['token' => $user->createToken('api')->plainTextToken];
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Invalidate previous tokens so the user only has one active session
        $user->tokens()->delete();

        return ['token' => $user->createToken('api')->plainTextToken];
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->noContent();
    }

    public function redirectToProvider(string $provider)
    {
        // Redirect the user to the chosen OAuth provider's auth page
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback(string $provider)
    {
        // Retrieve user details provided by the OAuth service
        $social = Socialite::driver($provider)->stateless()->user();

        // Either find an existing user by email or create a new one
        $user = User::firstOrCreate(
            ['email' => $social->getEmail()],
            ['name' => $social->getName() ?: $social->getNickname()]
        );

        $user->tokens()->delete();

        return ['token' => $user->createToken('api')->plainTextToken];
    }
}
