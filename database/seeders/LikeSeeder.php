<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;

class LikeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        Post::all()->each(function ($post) use ($users) {
            $likers = $users->random(rand(0, 6));
            foreach ($likers as $user) {
                Like::factory()->create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                ]);
            }
        });
    }
}
