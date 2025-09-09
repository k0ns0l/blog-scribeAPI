<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group User Management
 *
 * APIs for managing users
 */
class UserController extends Controller
{
    use AuthorizesRequests;
    /**
     * Get all users
     *
     * @authenticated
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer Number of users per page. Example: 10
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com",
     *       "posts_count": 5,
     *       "comments_count": 12,
     *       "created_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        return User::withCount(['posts', 'comments'])
            ->paginate($request->get('per_page', 15));
    }

    /**
     * Create a new user
     *
     * @authenticated
     * @bodyParam name string required The user's name. Example: Jane Doe
     * @bodyParam email string required The user's email. Example: jane@example.com
     * @bodyParam password string required The user's password (min 8 characters). Example: password123
     * @bodyParam password_confirmation string required Confirm the password. Example: password123
     *
     * @response 201 {
     *   "id": 1,
     *   "name": "Jane Doe",
     *   "email": "jane@example.com",
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json($user, 201);
    }

    /**
     * Get a specific user
     *
     * @urlParam user integer required The user ID. Example: 1
     *
     * @response {
     *   "id": 1,
     *   "name": "John Doe",
     *   "email": "john@example.com",
     *   "posts_count": 5,
     *   "comments_count": 12,
     *   "likes_count": 25,
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return $user->loadCount(['posts', 'comments', 'likes']);
    }

    /**
     * Update a user
     *
     * @authenticated
     * @urlParam user integer required The user ID. Example: 1
     * @bodyParam name string The user's name. Example: Updated Name
     * @bodyParam email string The user's email. Example: updated@example.com
     */
    public function update(Request $request, $user)
    {   
        $user = User::findOrFail($user);
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'email' => 'string|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($validated);
        return $user;
    }

    /**
     * Delete a user
     *
     * @authenticated
     * @urlParam user integer required The user ID. Example: 1
     *
     * @response 204
     */
    public function destroy($user)
    {
        $user = User::findOrFail($user);
        $this->authorize('delete', $user);
        $user->delete();
        return response()->json([
            'message' => 'User deleted successfully.'
        ], 200);    
    }

    /**
     * Get user's liked posts
     *
     * @authenticated
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer Number of posts per page. Example: 10
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Sample Post",
     *       "slug": "sample-post",
     *       "excerpt": "This is a sample post",
     *       "user": {
     *         "id": 2,
     *         "name": "Author Name"
     *       },
     *       "category": {
     *         "id": 1,
     *         "name": "Technology"
     *       }
     *     }
     *   ]
     * }
     */
    public function likedPosts(Request $request)
    {
        $user = auth()->user();

        return $user->likes()
            ->with(['post.user', 'post.category'])
            ->latest()
            ->paginate($request->get('per_page', 15))
            ->through(fn($like) => $like->post);
    }

    /**
     * Get user's comments
     *
     * @authenticated
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer Number of comments per page. Example: 10
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "content": "Great post!",
     *       "post": {
     *         "id": 1,
     *         "title": "Sample Post",
     *         "slug": "sample-post"
     *       },
     *       "created_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function comments(Request $request)
    {
        $user = auth()->user();

        return $user->comments()
            ->with('post:id,title,slug')
            ->latest()
            ->paginate($request->get('per_page', 15));
    }
}
