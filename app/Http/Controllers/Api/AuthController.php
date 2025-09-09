<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * APIs for user authentication and registration
 */
class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * @bodyParam name string required The user's name. Example: John Doe
     * @bodyParam email string required The user's email. Example: john@example.com
     * @bodyParam password string required The user's password (min 8 characters). Example: password123
     * @bodyParam password_confirmation string required Confirm the password. Example: password123
     *
     * @response 201 {
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "created_at": "2023-01-01T00:00:00.000000Z"
     *   },
     *   "token": "1|abc123def456..."
     * }
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'expires_in' => 'nullable|integer|min:1|max:525600', // Max 1 year in minutes
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Calculate expiration time (default 30 days)
        $expiresInMinutes = $request->input('expires_in', 43200); // 30 days default
        $expiresAt = now()->addMinutes($expiresInMinutes);
        
        $token = $user->createToken('auth_token', ['*'], $expiresAt);

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toISOString(),
            'exp' => $expiresAt->timestamp,
        ], 201);
    }

    /**
     * Login user
     *
     * @bodyParam email string required The user's email. Example: john@example.com
     * @bodyParam password string required The user's password. Example: password123
     *
     * @response {
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "email": "john@example.com"
     *   },
     *   "token": "1|abc123def456..."
     * }
     * @response 422 {
     *   "message": "The provided credentials are incorrect."
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'expires_in' => 'nullable|integer|min:1|max:525600', // Max 1 year in minutes
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        
        // Calculate expiration time (default 30 days)
        $expiresInMinutes = $request->input('expires_in', 43200); // 30 days default
        $expiresAt = now()->addMinutes($expiresInMinutes);
        
        // Create token with appropriate abilities based on role
        $abilities = $user->isAdmin() ? ['*'] : ['read', 'write'];
        $token = $user->createToken('auth_token', $abilities, $expiresAt);

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toISOString(),
            'exp' => $expiresAt->timestamp,
        ]);
    }

    /**
     * Logout user
     *
     * @authenticated
     *
     * @response {
     *   "message": "Successfully logged out"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Get authenticated user
     *
     * @authenticated
     *
     * @response {
     *   "id": 1,
     *   "name": "John Doe",
     *   "email": "john@example.com",
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function user(Request $request)
    {
        return $request->user();
    }
}