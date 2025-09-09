<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Categories
 *
 * APIs for managing blog categories
 */
class CategoryController extends Controller
{
    use AuthorizesRequests;
    /**
     * Get all categories
     *
     * @queryParam page integer The page number for pagination. Example: 1
     * @queryParam per_page integer Number of categories per page. Example: 10
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Technology",
     *       "slug": "technology",
     *       "description": "All about tech",
     *       "posts_count": 15,
     *       "created_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ]
     * }
     */
    public function index(Request $request)
    {
        return Category::withCount('posts')
            ->paginate($request->get('per_page', 15));
    }

    /**
     * Create a new category
     *
     * @authenticated
     * @bodyParam name string required The category name. Example: Technology
     * @bodyParam description string The category description. Example: All about technology
     *
     * @response 201 {
     *   "id": 1,
     *   "name": "Technology",
     *   "slug": "technology", 
     *   "description": "All about technology",
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function store(Request $request)
    {
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'description' => 'nullable|string',
        ]);

        $category = Category::create([
            ...$validated,
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json($category, 201);
    }

    /**
     * Get a specific category
     *
     * @urlParam category integer required The category ID. Example: 1
     *
     * @response {
     *   "id": 1,
     *   "name": "Technology",
     *   "slug": "technology",
     *   "description": "All about technology",
     *   "posts_count": 15,
     *   "created_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function show($category)
    {
        $category = Category::findOrFail($category);
        return $category->loadCount('posts');
    }

    /**
     * Update a category
     *
     * @authenticated
     * @urlParam category integer required The category ID. Example: 1
     * @bodyParam name string The category name. Example: Updated Technology
     * @bodyParam description string The category description.
     */
    public function update(Request $request, Category $category)
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => 'string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);
        return $category;
    }

    /**
     * Delete a category
     *
     * @authenticated
     * @urlParam category integer required The category ID. Example: 1
     *
     * @response 204
     */
    public function destroy($category)
    {
        $category = Category::findOrFail($category);
        $this->authorize('delete', $category);

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.'
        ], 200);
    }

    /**
     * Get posts in a category
     *
     * @urlParam category integer required The category ID. Example: 1
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
     *       }
     *     }
     *   ]
     * }
     */
    public function posts(Request $request, $category)
    {
        $category = Category::findOrFail($category);
        return $category->posts()
            ->with(['user', 'tags'])
            ->where('status', 'published')
            ->latest()
            ->paginate($request->get('per_page', 15));
    }
}