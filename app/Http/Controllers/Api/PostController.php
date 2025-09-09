<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Blog Posts
 *
 * APIs for managing blog posts
 */
class PostController extends Controller
{
    use AuthorizesRequests;
    /**
     * Get all posts
     *
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer Number of posts per page. Example: 10
     * @queryParam status string Filter by status (published, draft, archived). Example: published
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Sample Post",
     *       "slug": "sample-post",
     *       "excerpt": "This is a sample post",
     *       "status": "published",
     *       "published_at": "2023-01-01T00:00:00.000000Z",
     *       "user": {
     *         "id": 1,
     *         "name": "John Doe"
     *       },
     *       "category": {
     *         "id": 1,
     *         "name": "Technology"
     *       }
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "total": 50
     *   }
     * }
     */
    public function index(Request $request)
    {
        $query = Post::with(['user', 'category', 'tags']);
        
        // Apply visibility filters based on user role
        if (!auth()->check()) {
            // Guests can only see published posts
            $query->where('status', 'published');
        } elseif (!auth()->user()->isAdmin()) {
            // Non-admin authenticated users can see published posts or their own posts
            $query->where(function ($q) {
                $q->where('status', 'published')
                  ->orWhere('user_id', auth()->id());
            });
        }
        // Admins can see all posts (no additional filter)
        
        // Apply status filter if requested (only if user can see that status)
        if ($request->status) {
            if (!auth()->check() && $request->status !== 'published') {
                // Guests can only filter by published
                return response()->json(['message' => 'Access denied'], 403);
            }
            $query->where('status', $request->status);
        }

        return $query->latest()->paginate($request->get('per_page', 15));
    }

    /**
     * Create a new post
     *
     * @authenticated
     * @bodyParam title string required The title of the post. Example: My New Post
     * @bodyParam content string required The content of the post.
     * @bodyParam excerpt string The excerpt of the post.
     * @bodyParam category_id integer required The category ID. Example: 1
     * @bodyParam tag_ids array The tag IDs. Example: [1, 2, 3]
     * @bodyParam status string The status (draft, published). Example: draft
     *
     * @response 201 {
     *   "id": 1,
     *   "title": "My New Post",
     *   "slug": "my-new-post",
     *   "content": "Post content here...",
     *   "status": "draft",
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'status' => 'in:draft,published,archived',
        ]);

        $post = Post::create([
            ...$validated,
            'slug' => Str::slug($validated['title']),
            'user_id' => auth()->id(),
            'published_at' => ($validated['status'] ?? 'draft') === 'published' ? now() : null,
        ]);

        if (isset($validated['tag_ids'])) {
            $post->tags()->sync($validated['tag_ids']);
        }

        return response()->json($post->load(['user', 'category', 'tags']), 201);
    }

    /**
     * Get a specific post
     *
     * @urlParam post integer required The post ID. Example: 1
     *
     * @response {
     *   "id": 1,
     *   "title": "Sample Post",
     *   "slug": "sample-post",
     *   "content": "Full post content here...",
     *   "excerpt": "This is a sample post",
     *   "status": "published",
     *   "published_at": "2023-01-01T00:00:00.000000Z",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe"
     *   },
     *   "category": {
     *     "id": 1,
     *     "name": "Technology"
     *   },
     *   "tags": [
     *     {
     *       "id": 1,
     *       "name": "Laravel"
     *     }
     *   ],
     *   "likes_count": 25,
     *   "comments_count": 10
     * }
     */
    public function show($post)
    {
        $post = Post::findOrFail($post);    
        if ($post->status !== 'published') {
            if (!auth()->check() || (!auth()->user()->isAdmin() && auth()->id() !== $post->user_id)) {
                abort(404); // Hide unpublished posts
            }
        }
        
        return $post->load(['user', 'category', 'tags'])
            ->loadCount(['likes', 'comments']);
    }

    /**
     * Update a post
     *
     * @authenticated
     * @urlParam post integer required The post ID. Example: 1
     * @bodyParam title string The title of the post. Example: Updated Post Title
     * @bodyParam content string The content of the post.
     * @bodyParam excerpt string The excerpt of the post.
     * @bodyParam category_id integer The category ID. Example: 1
     * @bodyParam tag_ids array The tag IDs. Example: [1, 2, 3]
     * @bodyParam status string The status (draft, published). Example: published
     */
    public function update(Request $request, $post)
    {
        $post = Post::findOrFail($post);
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'string|max:255',
            'content' => 'string',
            'excerpt' => 'nullable|string',
            'category_id' => 'exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
            'status' => 'in:draft,published,archived',
        ]);

        if (isset($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        if (isset($validated['status']) && $validated['status'] === 'published' && !$post->published_at) {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        if (isset($validated['tag_ids'])) {
            $post->tags()->sync($validated['tag_ids']);
        }

        return $post->load(['user', 'category', 'tags']);
    }

    /**
     * Delete a post
     *
     * @authenticated
     * @urlParam post integer required The post ID. Example: 1
     *
     * @response 204
     */
    public function destroy($post)
    {
        $post = Post::findOrFail($post);
        $this->authorize('delete', $post);
        $post->delete();
        return response()->json([
            'message' => 'Post deleted successfully.'
        ], 200);
    }

    /**
     * Like a post
     *
     * @authenticated
     * @urlParam post integer required The post ID. Example: 1
     *
     * @response {
     *   "message": "Post liked successfully",
     *   "likes_count": 26
     * }
     */
    public function like(Post $post)
    {
        $like = Like::firstOrCreate([
            'user_id' => auth()->id(),
            'post_id' => $post->id,
        ]);

        return response()->json([
            'message' => 'Post liked successfully',
            'likes_count' => $post->likes()->count(),
        ]);
    }

    /**
     * Unlike a post
     *
     * @authenticated
     * @urlParam post integer required The post ID. Example: 1
     *
     * @response {
     *   "message": "Post unliked successfully",
     *   "likes_count": 24
     * }
     */
    public function unlike(Post $post)
    {
        Like::where('user_id', auth()->id())
            ->where('post_id', $post->id)
            ->delete();

        return response()->json([
            'message' => 'Post unliked successfully',
            'likes_count' => $post->likes()->count(),
        ]);
    }
}