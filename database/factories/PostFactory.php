<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(6);
        return [
            'title' => $title,
            'content' => fake()->paragraphs(5, true),
            'excerpt' => fake()->text(200),
            'slug' => \Illuminate\Support\Str::slug($title),
            'featured_image' => fake()->imageUrl(800, 600, 'technology'),
            'status' => fake()->randomElement(['draft', 'published']),
            'user_id' => \App\Models\User::factory(),
            'category_id' => \App\Models\Category::factory(),
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
