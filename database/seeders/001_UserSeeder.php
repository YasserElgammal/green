<?php

use App\Tables\UserTable;
use YasserElgammal\Green\Database\Seeders\Seeder;

/**
 * Seeds the `users` table with an admin account and a test user.
 *
 * Truncates the table first so re-running this seeder always
 * produces the same predictable state.
 */
class UserSeeder extends Seeder
{
    protected array $truncate = ['users'];

    public function run(): void
    {
        $users = new UserTable();

        $users->insert([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'is_admin' => true,
        ]);

        $users->insert([
            'name'     => 'Test User',
            'email'    => 'user@example.com',
            'password' => password_hash('secret', PASSWORD_BCRYPT),
            'is_admin' => false,
        ]);
    }
}
