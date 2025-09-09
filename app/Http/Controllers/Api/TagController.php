<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Tags
 *
 * APIs for managing blog tags
 */
class TagController extends Controller
{
    use AuthorizesRequests;
    /**
     * Get all tags
     *
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer Number of tags per page. Example: 10
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Laravel",
     *       "slug": "laravel",
     *       "posts_count": 25,
     *       "created_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        return Tag::withCount('posts')
            ->paginate($request->get('per_page', 15));
    }

    /**
     * Create a new tag
     *
     * @authenticated
     * @bodyParam name string required The tag name. Example: Laravel
     *
     * @response 201 {
     *   "id": 1,
     *   "name": "Laravel",
     *   "slug": "laravel",
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function store(Request $request)
    {
        $this->authorize('create', Tag::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags',
        ]);

        $tag = Tag::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json($tag, 201);
    }

    /**
     * Get a specific tag
     *
     * @urlParam tag integer required The tag ID. Example: 1
     *
     * @response {
     *   "id": 1,
     *   "name": "Laravel",
     *   "slug": "laravel",
     *   "posts_count": 25,
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function show($tag)
    {
        $tag = Tag::findOrFail($tag);
        return $tag->loadCount('posts');
    }

    /**
     * Update a tag
     *
     * @authenticated
     * @urlParam tag integer required The tag ID. Example: 1
     * @bodyParam name string The tag name. Example: Updated Laravel
     */
    public function update(Request $request, $tag)
    {
        $tag = Tag::findOrFail($tag);
        $this->authorize('update', $tag);

        $validated = $request->validate([
            'name' => 'string|max:255|unique:tags,name,' . $tag->id,
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $tag->update($validated);
        return $tag;
    }

    /**
     * Delete a tag
     *
     * @authenticated
     * @urlParam tag integer required The tag ID. Example: 1
     *
     * @response 204
     */
    public function destroy(Tag $tag)
    {
        $this->authorize('delete', $tag);

        $tag->delete();
        return response()->json([
            'message' => 'Tag deleted successfully.'
        ], 200);
    }

    /**
     * Get posts with a specific tag
     *
     * @urlParam tag integer required The tag ID. Example: 1
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
     *       "status": "published",
     *       "user": {
     *         "id": 1,
     *         "name": "John Doe"
     *       },
     *       "category": {
     *         "id": 1,
     *         "name": "Technology"
     *       }
     *     }
     *   ]
     * }
     */
    public function posts(Request $request)
    {
        $tag = Tag::findOrFail($request->route('tag'));
        return $tag->posts()
            ->with(['user', 'category'])
            ->where('status', 'published')
            ->latest()
            ->paginate($request->get('per_page', 15));
    }
}