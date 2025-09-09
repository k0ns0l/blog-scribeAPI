<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\Category;
use App\Models\User;
use App\Models\Tag;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $categories = Category::all();
        $tags = Tag::all();

        foreach ($categories as $category) {
            $posts = Post::factory(rand(3, 8))->create([
                'category_id' => $category->id,
                'user_id' => $users->random()->id,
            ]);

            $posts->each(function ($post) use ($tags) {
                $post->tags()->attach(
                    $tags->random(rand(1, 4))->pluck('id')->toArray()
                );
            });
        }
    }
}
