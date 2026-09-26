<?php

use App\Tables\PostTable;
use App\Tables\UserTable;
use YasserElgammal\Green\Database\Seeders\Seeder;

/**
 * Seeds the `posts` table with sample posts linked to the admin user.
 *
 * Depends on UserSeeder running first — the numeric prefix (002_)
 * guarantees that ordering automatically.
 */
class PostSeeder extends Seeder
{
    protected array $truncate = ['posts'];

    public function run(): void
    {
        // Look up the admin user inserted by UserSeeder
        $users = new UserTable();
        $admin = $users->fetchFirst('email', 'admin@example.com');

        if ($admin === null) {
            throw new \RuntimeException('PostSeeder requires UserSeeder to run first (admin user not found).');
        }

        $posts = new PostTable();

        $posts->insert([
            'user_id' => $admin->id,
            'title'   => 'Welcome to Green Framework',
            'status'  => 'published',
            'body'    => 'Green is a lightweight, explicit PHP framework built for clarity and control.',
        ]);

        $posts->insert([
            'user_id' => $admin->id,
            'title'   => 'Getting Started with Seeders',
            'status'  => 'draft',
            'body'    => 'Seeders let you populate your database with initial or test data via the CLI.',
        ]);
    }
}
