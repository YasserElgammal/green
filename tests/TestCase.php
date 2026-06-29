<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use YasserElgammal\Green\Database\Database;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

abstract class TestCase extends BaseTestCase
{
    protected ?Connection $connection = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Environment
        $_ENV['JWT_SECRET'] = 'test-secret-key-that-is-long-enough-32-chars-for-hs256';
        $_ENV['JWT_TTL'] = 3600;
        auth()->logout();

        // 2. Setup in-memory SQLite connection
        $connectionParams = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];
        
        $this->connection = DriverManager::getConnection($connectionParams);
        
        // 2. Inject into the framework's Database singleton
        Database::setConnection($this->connection);

        // 3. Create schema (SQLite compatible)
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        // Reset the singleton connection
        Database::setConnection(null);
        $this->connection = null;

        parent::tearDown();
    }

    private function createSchema(): void
    {
        // Users
        $this->connection->executeStatement("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                is_admin INTEGER NOT NULL DEFAULT 0,
                avatar TEXT,
                refresh_token TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Posts
        $this->connection->executeStatement("
            CREATE TABLE posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'draft',
                body TEXT NOT NULL,
                image TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )
        ");

        // Roles
        $this->connection->executeStatement("
            CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // User Roles pivot
        $this->connection->executeStatement("
            CREATE TABLE user_roles (
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL,
                PRIMARY KEY (user_id, role_id),
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
            )
        ");

        // Comments
        $this->connection->executeStatement("
            CREATE TABLE comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )
        ");

        // Likes
        $this->connection->executeStatement("
            CREATE TABLE likes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                comment_id INTEGER,
                post_id INTEGER,
                user_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (comment_id) REFERENCES comments (id) ON DELETE CASCADE,
                FOREIGN KEY (post_id) REFERENCES posts (id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            )
        ");
    }

    protected function seed(): void
    {
        $password = password_hash('password', PASSWORD_DEFAULT);
        $this->connection->insert('users', ['name' => 'Test User', 'email' => 'test@example.com', 'password' => $password]);
        $this->connection->insert('users', ['name' => 'Admin User', 'email' => 'admin@example.com', 'password' => $password, 'is_admin' => 1]);
        
        $this->connection->insert('posts', [
            'user_id' => 1,
            'title'   => 'Test Post',
            'status'  => 'published',
            'body'    => 'This is a test post body.'
        ]);
    }
}
