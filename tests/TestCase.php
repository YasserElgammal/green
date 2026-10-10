<?php

namespace Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use YasserElgammal\Green\Application;
use YasserElgammal\Green\Database\Database;
use YasserElgammal\Green\ErrorHandling\GreenErrorKernel;
use YasserElgammal\Green\Session\SessionManager;

abstract class TestCase extends PhpUnitTestCase
{
    protected Application $app;
    protected Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__));
        }

        $_ENV['JWT_SECRET'] = 'test-secret-that-is-at-least-thirty-two-characters';
        $_ENV['JWT_TTL'] = 3600;

        $this->app = new Application(basePath: BASE_PATH);
        $app = $this->app;
        require BASE_PATH . '/routes/web.php';
        require BASE_PATH . '/routes/api.php';

        $this->app->instance(
            SessionManager::class,
            new SessionManager(new Session(new MockArraySessionStorage())),
        );

        auth()->logout();
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        Database::setConnection($this->connection);
        $this->connection->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, refresh_token TEXT UNIQUE, created_at DATETIME, updated_at DATETIME)');
    }

    protected function tearDown(): void
    {
        $this->app->make(GreenErrorKernel::class)->unregister();
        Database::setConnection(null);
        parent::tearDown();
    }

    protected function createUser(string $email = 'user@example.com'): object
    {
        return (new \App\Tables\UserTable())->insert([
            'name' => 'Demo User',
            'email' => $email,
            'password' => password_hash('password', PASSWORD_DEFAULT),
        ]);
    }
}
