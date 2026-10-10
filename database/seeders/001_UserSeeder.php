<?php

use App\Tables\UserTable;
use YasserElgammal\Green\Database\Seeders\Seeder;

class UserSeeder extends Seeder
{
    protected array $truncate = ['users'];

    public function run(): void
    {
        (new UserTable())->insert([
            'name' => 'Demo User',
            'email' => 'user@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
        ]);
    }
}
