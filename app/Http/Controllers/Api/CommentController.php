<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

/**
 * @group Comments
 *
 * APIs for managing blog comments
 */
class CommentController extends Controller
{
    use AuthorizesRequests;
    /**
     * Get comments for a post
     *
     * @urlParam post integer required The post ID. Example: 1
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer Number of comments per page. Example: 10
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "content": "Great post!",
     *       "user": {
     *         "id": 1,
     *         "name": "John Doe"
     *       },
     *       "created_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request, Post $post = null)
    {
        $query = $post ? $post->comments() : Comment::query();
        
        return $query->with('user')
            ->latest()
            ->paginate($request->get('per_page', 15));
    }

    /**
     * Create a new comment
     *
     * @authenticated
     * @bodyParam content string required The comment content. Example: This is a great post!
     * @bodyParam post_id integer required The post ID. Example: 1
     *
     * @response 201 {
     *   "id": 1,
     *   "content": "This is a great post!",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe"
     *   },
     *   "post": {
     *     "id": 1,
     *     "title": "Sample Post"
     *   },
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'post_id' => 'required|exists:posts,id',
        ]);

        $comment = Comment::create([
            'content' => $validated['content'],
            'post_id' => $validated['post_id'],
            'user_id' => auth()->id(),
        ]);

        return response()->json($comment->load(['user', 'post']), 201);
    }

    /**
     * Get a specific comment
     *
     * @urlParam comment integer required The comment ID. Example: 1
     *
     * @response {
     *   "id": 1,
     *   "content": "This is a great post!",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe"
     *   },
     *   "post": {
     *     "id": 1,
     *     "title": "Sample Post"
     *   },
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function show(Comment $comment)
    {
        return $comment->load(['user', 'post']);
    }

    /**
     * Update a comment
     *
     * @authenticated
     * @urlParam comment integer required The comment ID. Example: 1
     * @bodyParam content string required The updated comment content. Example: Updated comment text
     */
    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $comment->update($validated);
        return $comment->load(['user', 'post']);
    }

    /**
     * Delete a comment
     *
     * @authenticated
     * @urlParam comment integer required The comment ID. Example: 1
     *
     * @response 204
     */
    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);
        $comment->delete();
        return response()->json([
            'message' => 'Comment deleted successfully.'
        ], 200);
    }
}