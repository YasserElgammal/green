# 🌿 Green Framework Master Documentation

Welcome to the official technical reference for the **Green PHP Framework**. This document provides a deep dive into the architecture, components, and best practices for building modern web applications and APIs.

---

## 🏗️ 1. Core Architecture

### 1.1 Application Lifecycle
Green follows a standard Front Controller pattern:
1. **Entry Point**: `public/index.php` captures the HTTP request.
2. **Bootstrapping**: Dotenv loads config, and `View::init()` prepares the Twig engine.
3. **Routing**: `Application->handle()` passes the request to the `Router`.
4. **Middleware**: The request flows through global and route-level middleware pipelines.
5. **Dispatching**: The Router invokes the Controller method associated with the current URI.
6. **Response**: The Controller returns a `Response` (or an array for JSON), which is then sent to the client via `send()`.
7. **Exception Handling**: Any uncaught `Throwable` is caught by the global `ExceptionHandler` to provide a clean response.

### 1.2 Dependency Injection (Auto-wiring)
Green automatically injects common dependencies into your controller methods:
- `YasserElgammal\Green\Http\Request`: The current request object.
- `YasserElgammal\Green\Http\Payload`: Any custom payload subclasses (validated automatically).

---

## 🛣️ 2. Routing & Middleware

### 2.1 Attribute-based Routing
Routes are defined using PHP 8 Attributes directly above controller methods.

```php
#[Route('GET', '/posts/{id}', middleware: [AuthMiddleware::class])]
public function show(int $id) { ... }
```

### 2.2 Middleware Pipeline
Middleware must implement `YasserElgammal\Green\Middleware\MiddlewareInterface`.

- **Global Middleware**: Defined in `public/index.php` via `$app->router->addGlobalMiddleware()`.
- **Route Middleware**: Defined in the `#[Route]` attribute.

### 2.3 Redirects
Use the `redirect($url)` helper to return a `RedirectResponse`.

```php
return redirect('/login');
```

---

## 🗄️ 3. Database: Table Gateway & Models

Green uses a **Table Gateway** pattern built on top of **Doctrine DBAL**.

### 3.1 Models (The State)
Models are pure Data Transfer Objects (DTOs). They define the structure but contain no database logic.

```php
class Post extends Model {
    protected string $table = 'posts';
}
```

### 3.2 Table Gateway (The Persistence)
The `Table` class handles all CRUD operations and returns hydrated Models.

```php
$posts = new Table(new Post());
$allPosts = $posts->fetchAll();
```

### 3.3 Relations & Eager Loading
Relations are defined in the `Table` class via the `$relations` array.

#### Supported Relation Types:
- `belongsTo`
- `hasOne`
- `hasMany`
- `manyToMany` (requires a pivot table)

#### Eager Loading (Include)
To avoid N+1 query problems, use the `include()` method. It supports dot-notation for nested relations.

```php
$posts = new PostTable();
$results = $posts->include('author,comments.author')->fetchAll();
```

---

## 🌐 4. API Layer: Transformers & Pagination

### 4.1 Smart Transformers
Transformers define how Models are serialized to JSON. They automatically handle nested relations if they were eager-loaded via `include()`.

```php
class PostTransformer extends Transformer {
    public function transform(Model $model): array {
        return [
            'id'    => $model->id,
            'title' => $model->title,
        ];
    }

    protected function includes(): array {
        return ['author' => new UserTransformer()];
    }
}
```

### 4.2 Unified Pagination
The `paginate()` method on the `Table` class returns a standardized structure for collections.

```php
$results = $posts->paginate(perPage: 15, page: 1);
// Returns ['data' => [...], 'meta' => [...]]
```

---

## 🧪 5. Logic: Payloads & Validation

### 5.1 Payloads
Payloads are dedicated classes for validating incoming request data. They extend `YasserElgammal\Green\Http\Payload`.

```php
class StorePostPayload extends Payload {
    public function rules(): array {
        return [
            'title' => v::stringType()->length(5, 100),
            'body'  => v::stringType()->notEmpty(),
        ];
    }
}
```

When type-hinted in a controller method, the framework **automatically** validates the request before the method is even executed.

---

## 🔒 6. Security & Sessions

### 6.1 Session Management
Use the `session()` helper to manage state.
- `session()->set('key', 'value')`
- `session()->get('key')`
- `session()->flash('message', 'Saved!')` (Self-destructing data after one request).

### 6.2 Password Hashing
The `PasswordHasher` class uses `bcrypt` by default.
```php
$hasher = new PasswordHasher();
$hash = $hasher->hash('secret');
```

---

## ⚠️ 7. Exception Handling & Debug Mode

Green features a robust global error handling system.

### 7.1 Debug Mode
Controlled via `APP_DEBUG` in your `.env` file.
- **`true`**: Shows detailed stack traces, file paths, and a dedicated Dark Mode debug UI.
- **`false`**: Shows a clean, user-friendly "Oops" page with sanitized error messages.

### 7.2 API Errors
The exception handler automatically detects `application/json` requests and returns a structured JSON error response instead of HTML.

---

## 🛠️ 8. Helper Reference Cheat Sheet

| Helper                         | Returns            | Usage                               |
| :----------------------------- | :----------------- | :---------------------------------- |
| `view(tpl, data)`              | `string`           | Renders a Twig template.            |
| `response_json(data, status)`  | `JsonResponse`     | Returns a JSON response.            |
| `session()`                    | `SessionManager`   | Access the session engine.          |
| `redirect(url)`                | `RedirectResponse` | Triggers a browser redirect.        |
| `transform(data, transformer)` | `JsonResponse`     | Serializes models via Transformers. |
| `paginate(items, per, page)`   | `JsonResponse`     | Paginates any array/collection.     |

---

## 🔧 9. Console Commands

Green provides a simple CLI interface for development and code generation.

### 📋 Available Commands

```bash
php green help               # Display help for a command
php green list               # List all available commands
php green serve              # Start development server

php green create:model Car         # Create a new model
php green create:controller Car    # Create a new controller
```

## 🔧 10. Migrations & Schema Builder

### `migrate` — Run pending migrations

```bash
php green migrate
```

Runs all migration files in `database/migrations/` that have not been recorded in the `migrations` table. Groups them into a new **batch**.

```bash
# Dry-run: print SQL without executing
php green migrate --dry

# Force: allow DROP operations (disables safe mode)
php green migrate --force

# Both
php green migrate --dry --force
```

**Example output:**
```
┌────────────────────────────────────────┐
│  Green Framework — Migrations          │
└────────────────────────────────────────┘

[INFO]  Migrated: 2026_01_01_000001_create_users_table
[INFO]  Migrated: 2026_01_01_000002_add_phone_to_users
[INFO]  Migrated: 2026_01_01_000003_create_posts_table

[INFO]  Done. 3 migration(s) run.
```

---

### `migrate:rollback` — Roll back the last batch

```bash
php green migrate:rollback
```

Calls `down()` on every migration in the **last batch**, in reverse order.

```bash
php green migrate:rollback --dry    # preview SQL only
php green migrate:rollback --force  # allow DROP in down()
```

---

### `migrate:status` — Show migration status

```bash
php green migrate:status
```

```
┌─────────────────────────────────────────────┐
│  Green Framework — Migration Status         │
└─────────────────────────────────────────────┘

  Migration                                     Status
  ──────────────────────────────────────────────────────
  2026_01_01_000001_create_users_table          ✔ ran
  2026_01_01_000002_add_phone_to_users          ✔ ran
  2026_01_01_000003_create_posts_table          ⏳ pending
```

---

### `create:migration` — Generate a migration file

```bash
php green create:migration create_orders_table
php green create:migration add_phone_to_users
```

**Does not require a DB connection.**

The table name is automatically inferred from the migration name:

| Migration name | Guessed table |
|---|---|
| `create_orders_table` | `orders` |
| `add_email_to_users` | `users` |

---

## Writing Migrations

Every migration file must:
- Live in `database/migrations/`
- Be named `YYYY_MM_DD_HHMMSS_snake_name.php`
- Contain **one class** whose name is the PascalCase version of the filename suffix
- Extend `Green\Database\Migrations\Migration`
- Implement `up()` and `down()`

### Create a table

```php
use Green\Database\Migrations\Migration;
use Green\Database\Schema\Blueprint;
use Green\Database\Schema\Schema;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();                            // BIGINT AUTO_INCREMENT PK
            $table->string('name', 100);
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 20)->default('member');
            $table->boolean('is_active')->default(true);
            $table->text('bio')->nullable();
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();                    // created_at, updated_at
            $table->index(['role', 'is_active']);    // composite index
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
}
```

### Alter a table

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone', 20)->nullable();    // ADD COLUMN
        $table->modify(Column::string('name', 200)); // MODIFY COLUMN
        $table->drop('legacy_field');               // DROP COLUMN (blocked in safe mode)
        $table->index('phone');                     // CREATE INDEX
    });
}
```

---

## Schema Builder Reference

### `Schema::` static methods

| Method | Description |
|---|---|
| `Schema::create(table, fn)` | Create a new table |
| `Schema::table(table, fn)` | Alter an existing table |
| `Schema::drop(table)` | Drop a table (safe-mode blocked) |
| `Schema::dropIfExists(table)` | Drop if exists (safe-mode blocked) |
| `Schema::hasTable(table)` | Check table existence → bool |
| `Schema::hasColumn(table, col)` | Check column existence → bool |
| `Schema::setDryRun(bool)` | Enable/disable dry-run mode |
| `Schema::setSafeMode(bool)` | Enable/disable safe mode |
| `Schema::getDryRunLog()` | Return collected SQL strings |

### `Blueprint` methods (inside callbacks)

| Method | SQL produced |
|---|---|
| `$table->id()` | `BIGINT NOT NULL AUTO_INCREMENT` + PRIMARY KEY |
| `$table->string(name, len?)` | `VARCHAR(255)` |
| `$table->integer(name)` | `INT` |
| `$table->bigInteger(name)` | `BIGINT` |
| `$table->text(name)` | `TEXT` |
| `$table->boolean(name)` | `TINYINT(1)` |
| `$table->timestamp(name)` | `TIMESTAMP` |
| `$table->date(name)` | `DATE` |
| `$table->decimal(name, p, s)` | `DECIMAL(p,s)` |
| `$table->json(name)` | `JSON` |
| `$table->timestamps()` | `created_at`, `updated_at` both nullable TIMESTAMP |
| `$table->add(Column)` | Queues ADD COLUMN (in ALTER) |
| `$table->modify(Column)` | Queues MODIFY COLUMN |
| `$table->drop(colName)` | Queues DROP COLUMN (safe-mode blocked) |
| `$table->ensure(Column)` | Smart upsert — see below |
| `$table->index(cols, name?)` | CREATE INDEX |

### Column modifiers (chainable)

```php
$table->string('email')
    ->nullable()           // NULL instead of NOT NULL
    ->unique()             // UNIQUE constraint
    ->default('guest')     // DEFAULT value
    ->autoIncrement();     // AUTO_INCREMENT
```

---

## Column Types

| Blueprint method | MySQL type | Notes |
|---|---|---|
| `id()` | `BIGINT AUTO_INCREMENT` | Always sets PRIMARY KEY |
| `string(name, len)` | `VARCHAR(len)` | Default len = 255 |
| `integer(name)` | `INT` | |
| `bigInteger(name)` | `BIGINT` | |
| `text(name)` | `TEXT` | |
| `boolean(name)` | `TINYINT(1)` | Use `->default(true/false)` |
| `timestamp(name)` | `TIMESTAMP` | |
| `date(name)` | `DATE` | |
| `decimal(name, p, s)` | `DECIMAL(p,s)` | Default 8,2 |
| `json(name)` | `JSON` | MySQL 5.7.8+ |

---

## Advanced Features

### `ensure()` — Idempotent column management

The most powerful feature. Runs a live introspection against `information_schema.COLUMNS` and decides automatically:

```
Column missing?         → ALTER TABLE … ADD COLUMN
Column exists, differs? → ALTER TABLE … MODIFY COLUMN
Column identical?       → (no-op, nothing emitted)
```

```php
Schema::table('users', function (Blueprint $table) {
    // Safe to run in every deploy — only acts when needed
    $table->ensure(Column::string('phone', 20)->nullable());
    $table->ensure(Column::boolean('mfa_enabled')->default(false));
    $table->ensure(Column::text('bio')->nullable());
});
```

Useful for: feature-flag columns, platform-agnostic schema sync, re-runnable setup scripts.

---

### Dry-run mode

Preview every SQL statement without touching the database:

```bash
php green migrate --dry
```

```
[WARN]  DRY RUN MODE — no SQL will be executed.

SQL that would be executed:
  CREATE TABLE `users` (
    `id` BIGINT AUTO_INCREMENT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    ...
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

  CREATE INDEX `idx_users_role_is_active` ON `users` (`role`, `is_active`);
```

The dry-run log is also accessible programmatically:

```php
Schema::setDryRun(true);
Schema::create('test', fn($t) => $t->string('x'));
$sql = Schema::getDryRunLog();  // ['CREATE TABLE `test` ...']
```

---

### Safe mode

Safe mode is **ON by default**. It blocks all destructive operations:

- `Schema::drop()`
- `Schema::dropIfExists()`
- `Blueprint::drop()`

```bash
# Will throw: "Safe mode: DROP TABLE `users` requires --force."
php green migrate:rollback

# Permitted
php green migrate:rollback --force
```

Programmatic control:

```php
Schema::setSafeMode(false);   // dangerous — use with care
```

---

### Batch tracking

Every `php green migrate` call groups all newly-run migrations into one **batch** (integer, auto-incremented). The `migrations` table looks like:

```
id  migration                                   batch  ran_at
1   2026_01_01_000001_create_users_table        1      2026-04-01 12:00:00
2   2026_01_01_000002_add_phone_to_users        1      2026-04-01 12:00:00
3   2026_01_01_000003_create_posts_table        2      2026-04-11 09:00:00
```

`migrate:rollback` always reverses the **highest** batch only, in reverse file order.

---

## 🌐 11. Translation & Localization

The Green Framework features a powerful, framework-agnostic translation engine.

### Setup and Configuration
Translations are configured via environment variables in your `.env` file:
```env
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_LANG_PATH=lang
APP_TRANSLATION_CACHE_PATH=storage/cache/translations
```

### Global Helpers
The engine exposes two primary global helper functions that can be used anywhere, including inside Twig templates:

#### `t(key, replace = [], locale = null)`
Translates a given string key.
```php
// Basic usage
t('home.welcome');

// With interpolation
t('layout.hello', ['name' => 'Yasser']);
```

Inside a Twig template:
```twig
<h2>{{ t('home.welcome') }}</h2>
```

#### `trans_choice(key, count, replace = [], locale = null)`
Handles language-specific pluralization based on CLDR rules (e.g. English one/other, Arabic 6-form pluralization).
```php
trans_choice('posts.show.comments_count', 5, ['count' => 5]);
```

Inside a Twig template:
```twig
<h4>{{ trans_choice('posts.show.comments_count', post.comments|length, {'count': post.comments|length}) }}</h4>
```

### JSON File Provider
By default, the framework uses the `JsonFileProvider`. Translations are stored as JSON files inside the `lang/` directory, organized by locale and **group**.

#### Key Resolution Rule
The **first dot-segment** of a translation key determines the **filename** (group). The remaining segments are the path inside that file:

| Key | File | Lookup path |
|---|---|---|
| `t('home.welcome')` | `lang/en/home.json` | `welcome` |
| `t('auth.login.title')` | `lang/en/auth.json` | `login.title` |
| `t('errors.oops.heading')` | `lang/en/errors.json` | `oops.heading` |
| `t('welcome')` *(no dot)* | `lang/en/messages.json` | `welcome` |

> **Note:** Keys with no dot default to the `messages` group.

#### Directory Structure
```
lang/
├── en/
│   ├── home.json
│   ├── auth.json
│   ├── layout.json
│   ├── errors.json
│   └── posts.json
└── ar/
    ├── home.json
    └── ...
```

#### Example: `lang/en/home.json`
```json
{
    "welcome": "Welcome to Green Framework",
    "description": "This is the home page rendered via Twig."
}
```

#### Example: `lang/en/layout.json`
```json
{
    "hello": "Hello, :name",
    "login": "Login",
    "logout": "Logout"
}
```
