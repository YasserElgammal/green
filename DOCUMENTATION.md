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

### 1.2 Core Service Providers

`Application` registers core framework services before handling requests. The skeleton uses these providers automatically:

| Provider | Responsibility |
| :--- | :--- |
| `SignalServiceProvider` | Registers the `SignalDispatcher` and the `signal()` helper. |
| `AuthServiceProvider` | Registers the authorization gate and the `authorizer()` helper. |
| `DatabaseServiceProvider` | Registers the Doctrine DBAL connection pool used by `Database`. |
| `CacheServiceProvider` | Registers the `CacheManager` and the `cache()` helper. |

Application providers listed in `config/app.php` are registered after the core providers, then every provider is booted.

### 1.3 Dependency Injection (Auto-wiring)
Green automatically injects common dependencies into your controller methods:
- `YasserElgammal\Green\Http\Request`: The current request object.
- `YasserElgammal\Green\Http\Payload`: Any custom payload subclasses (validated automatically).

---

## 🛣️ 2. Routing & Middleware

### 2.1 Attribute-based Routing
Routes are defined using PHP 8 Attributes directly above controller methods.

```php
#[Route('GET', '/posts/{id}', middleware: [AuthMiddleware::class, LocaleMiddleware::class])]
public function show(int $id) { ... }
```

The `middleware` argument accepts an array, so a route can run one or more middleware classes in order:

```php
#[Route('GET', '/users/{id}', middleware: [AuthMiddleware::class, AuditMiddleware::class])]
public function show(Request $request, int $id): array
{
    // ...
}
```

### 2.2 URL Generation

Routes may be named with the `name` argument on the `Route` attribute:

```php
#[Route('GET', '/users/{id}', name: 'users.show')]
public function show(int $id): array
{
    // ...
}
```

The Router stores named routes and `UrlGenerator` builds URLs for them. Use the `route()` helper from controllers, views, or services:

```php
$url = route('users.show', ['id' => 1]); // /users/1
```

Route parameters in `{braces}` are replaced from the parameter array. Extra parameters are appended as a query string.

### 2.3 Routing Internals

Application code still registers controller routes the same way, through `#[Route]` attributes and `$app->router->registerRoutesFromController(...)`. Internally, routing is split into focused collaborators:

- `RouteRegistry` stores registered routes, named routes, global middleware, and fallback handlers.
- `RouteRegistrar` scans controller attributes and registers each action.
- `RouteMatcher` matches the incoming method and path, including dynamic `{parameters}`.
- `MatchedRouteDispatcher` resolves policies, builds the middleware chain, and invokes the controller action.
- `RouteInvoker` maps request and route parameters into the controller method arguments.

Use `route('name', ['id' => 1])` for named route URLs; the helper delegates to the framework URL generator.

### 2.4 Middleware Pipeline
Middleware must implement `YasserElgammal\Green\Middleware\MiddlewareInterface`.

- **Global Middleware**: Defined in `public/index.php` via `$app->router->addGlobalMiddleware()`.
- **Route Middleware**: Defined in the `#[Route]` attribute using the `middleware` array.

### 2.5 Redirects
Use the `redirect($url)` helper to return a `RedirectResponse`.

```php
return redirect('/login');
```

### 2.6 Route Policies

Controller actions can be protected with policy attributes. The router reads `#[PolicyAttribute]` before invoking the action and calls the global authorizer.

```php
#[Route('PUT', '/users/{id}', name: 'users.update')]
#[PolicyAttribute('update', 'id')]
public function update(Request $request, int $id): array
{
    // ...
}
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

Models track their original hydrated state so the Table layer can persist only changed fields.

```php
$post->syncOriginal();
$post->title = 'Updated title';

$post->getDirty();          // ['title' => 'Updated title']
$post->isDirty();           // true
$post->isDirty('title');    // true
$post->isClean('body');     // true
$post->getOriginal('title');
```

Hydrated models call `syncOriginal()` automatically after they are read from the database.

### 3.2 Table Gateway (The Persistence)
The `Table` class handles all CRUD operations and returns hydrated Models.

```php
$posts = new Table(new Post());
$allPosts = $posts->fetchAll();
```

`Table::save()` uses dirty tracking. Existing models update only dirty fields, and clean models are skipped without issuing an update query.

```php
$post = $posts->fetchById(1);
$post->title = 'Updated title';
$posts->save($post); // updates title and updated_at
```

Tables manage timestamps automatically when timestamps are enabled:

```php
protected bool $timestamps = true;
```

With timestamps enabled, `created_at` and `updated_at` are set on insert, and `updated_at` is refreshed on update. Disable this per table when the table does not have timestamp columns:

```php
class AuditLogTable extends Table
{
    protected bool $timestamps = false;
}
```

### 3.3 Fluent Helpers (Conditional/Inspection API)

Table Gateway provides fluent helper methods `when()` and `tap()` to build conditional query chains and inspect Table states without breaking the chain.

#### `when()`
Executes a callback only when the first argument evaluates to true. An optional default fallback callback can be passed.

```php
$posts = $postsTable
    ->when($request->has('featured'), function ($table, $value) {
        // Only run when 'featured' is truthy in the request
        $table->include('likes');
    })
    ->fetchAll();
```

#### `tap()`
Allows you to inspect or perform side effects on the `Table` instance in the middle of a fluent chain.

```php
$posts = $postsTable
    ->include('author')
    ->tap(function ($table) {
        // Debug or conditionally inspect the table state
        error_log('Fetching posts with author includes.');
    })
    ->fetchAll();
```

### 3.4 Database Querying

Green's database layer uses a Table Gateway plus a small fluent query wrapper. Models stay as data containers; `Table` owns persistence; `GreenQuery` owns readable filtering, ordering, result fetching, and aggregate queries.

#### Starting a Query

```php
$users = (new UserTable(new User()))
    ->query()
    ->where('status', 'active')
    ->latest()
    ->fetch();
```

`Table::query()` starts from the table's Doctrine DBAL builder and returns a `GreenQuery` instance. The final result methods hydrate rows back into model instances and still honor pending includes queued on the table.

```php
$users = $userTable
    ->include('posts')
    ->query()
    ->where('role', 'admin')
    ->fetch();
```

Queries can be paginated after filters and ordering have been applied:

```php
$results = $postTable
    ->query()
    ->where('status', 'published')
    ->latest()
    ->paginate(perPage: 15, page: 1);
```

#### Conditions

Basic conditions support two common forms:

```php
$query->where('email', 'lara@green.dev');
$query->where('age', '>=', 18);
$query->where(['status' => 'active', 'role' => 'admin']);
$query->orWhere('score', '>=', 90);
```

Grouped conditions keep OR logic readable:

```php
$query
    ->where('status', 'active')
    ->whereGroup(fn ($query) => $query
        ->where('role', 'admin')
        ->orWhere('score', '>=', 90)
    );

$query->orWhereGroup(fn ($query) => $query
    ->where('role', 'editor')
    ->where('age', '>=', 30)
);
```

Supported operators for `where()` and `orWhere()` are `=`, `!=`, `<>`, `>`, `>=`, `<`, `<=`, `LIKE`, and `NOT LIKE`.

#### Helper Conditions

The query wrapper includes common SQL helpers:

```php
$query->whereIn('role', ['admin', 'editor']);
$query->orWhereIn('role', ['owner']);
$query->whereNotIn('status', ['banned']);
$query->orWhereNotIn('role', ['guest']);

$query->whereNull('deleted_at');
$query->orWhereNull('archived_at');
$query->whereNotNull('created_at');
$query->orWhereNotNull('deleted_at');

$query->whereBetween('age', 18, 40);
$query->orWhereBetween('score', 80, 100);
$query->whereNotBetween('age', 0, 17);
$query->orWhereNotBetween('score', 0, 10);

$query->whereLike('email', '%@green.dev');
$query->orWhereLike('name', 'Mo%');
$query->whereNotLike('email', '%example.com');
$query->orWhereNotLike('name', 'Test%');
```

Empty list conditions are deterministic: `whereIn('id', [])` matches no rows, while `whereNotIn('id', [])` does not filter rows out.

Column names are validated before they are interpolated into SQL. Values are always bound as query parameters.

#### Ordering and Windows

```php
$query->orderBy('name');
$query->orderBy('created_at', 'desc');
$query->latest();
$query->latest('published_at');
$query->oldest();
$query->limit(20)->offset(40);
```

#### Results

```php
$users = $query->fetch();
$user = $query->first();
$user = $query->firstRequired();
```

`firstRequired()` is useful when absence is exceptional and the caller should not continue with `null`.

#### Aggregates

Aggregates reuse the same conditions already applied to the query:

```php
$count = $userTable->query()->where('status', 'active')->count();
$exists = $userTable->query()->where('email', $email)->exists();
$total = $userTable->query()->whereNull('deleted_at')->sum('score');
$average = $userTable->query()->where('status', 'active')->avg('score');
$lowest = $userTable->query()->min('score');
$highest = $userTable->query()->max('score');
```

`sum()` returns `0` when no rows match. `avg()` returns `null` when no rows match.

#### Table Aliases

Tables keep concise aliases for common fetches:

```php
$users = $userTable->all();
$user = $userTable->find(1);
$user = $userTable->findRequired(1);
```

For fully custom SQL, use `builder()` and hand the builder back to the table for hydration:

```php
$builder = $userTable->builder()
    ->where('status = :status')
    ->setParameter('status', 'active');

$users = $userTable->fetchFromBuilder($builder);
```

#### Transactions

Use `Database::transaction()` to run work atomically. The callback return value is returned to the caller.

```php
$id = Database::transaction(function () use ($userTable) {
    $user = $userTable->insert([
        'name' => 'Lara',
        'email' => 'lara@green.dev',
    ]);

    return $user->id;
});
```

If the callback throws, the transaction is rolled back and the exception is rethrown.

### 3.5 Relations & Eager Loading
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

### 3.6 Relation Aggregations
Green provides native support for executing aggregate functions (`COUNT`, `EXISTS`, `SUM`, `AVG`, `MIN`, `MAX`) on related tables. These can be executed programmatically using fluent methods on the `Table` class.

All aggregation methods accept a single relation name (or format of `relation:column` where required) or an array of relations. The computed values are automatically type-casted and attached to the parent models as attributes (accessible as dynamic properties).

#### Programmatic Aggregation API

| Method | Argument Format | Result Type | Target Attribute Name |
| :--- | :--- | :--- | :--- |
| `includeCount(string\|array $relations)` | `relation` | `int` | `{relation}_count` |
| `includeExists(string\|array $relations)` | `relation` | `bool` | `{relation}_exists` |
| `includeSum(string\|array $relations)` | `relation:column` | `float` | `{relation}_sum_{column}` |
| `includeAvg(string\|array $relations)` | `relation:column` | `float\|null` | `{relation}_avg_{column}` |
| `includeMin(string\|array $relations)` | `relation:column` | `mixed` | `{relation}_min_{column}` |
| `includeMax(string\|array $relations)` | `relation:column` | `mixed` | `{relation}_max_{column}` |

#### Usage Examples

```php
$postsTable = new PostTable();

$posts = $postsTable
    ->includeCount('comments')          // sets comments_count (int)
    ->includeExists('likes')            // sets likes_exists (bool)
    ->includeAvg('reviews:rating')      // sets reviews_avg_rating (float/null)
    ->includeMin('orders:total')        // sets orders_min_total (mixed)
    ->includeMax('orders:total')        // sets orders_max_total (mixed)
    ->fetchAll();
```

You can also pass arrays to perform multiple calculations at once:
```php
$postsTable->includeCount(['comments', 'likes'])
           ->includeSum(['orders:total', 'orders:tax'])
           ->fetchAll();
```

### 3.6 CRUD Operations

Green provides a streamlined way to perform Create, Read, Update, and Delete operations via the `Table` class.

#### Create
```php
$postsTable = new PostTable();
$id = $postsTable->insert([
    'title' => 'My New Post',
    'body' => 'Post content here',
    'user_id' => 1
]);
```

#### Read
```php
// Fetch by ID
$post = $postsTable->fetchById($id);

// Fetch all
$allPosts = $postsTable->fetchAll();

// Custom Queries using QueryBuilder
$query = $postsTable->builder()->where('status', 'published');
$publishedPosts = $postsTable->fetchAllFromBuilder($query);
```

#### Update
```php
$postsTable->update($id, [
    'title' => 'Updated Title'
]);
```

#### Delete
```php
$postsTable->delete($id);
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
The framework returns the same pagination structure for table-level and query-level pagination.

Use `query()->paginate()` when the page depends on filters, search conditions, joins, or ordering:

```php
$results = $posts
    ->query()
    ->where('status', 'published')
    ->latest()
    ->paginate(perPage: 15, page: 1, withCount: true);
// Returns ['data' => [...], 'meta' => [...]]
```

`Table::paginate()` remains available as a shortcut for simple full-table pagination:

```php
$results = $posts->paginate(perPage: 15, page: 1);
```

#### Smart COUNT Elimination
By default, `paginate()` runs a `COUNT(*)` query to calculate `total_items` and `total_pages`. For large datasets, pass `withCount: false` to skip the count query:

```php
$results = $posts
    ->query()
    ->where('status', 'published')
    ->paginate(perPage: 15, page: 1, withCount: false);
```

When `withCount: false` is used:
- The framework skips the expensive `COUNT(*)` query.
- It queries `perPage + 1` rows to determine if a next page exists.
- The `meta` keys `total_items` and `total_pages` return `null`.

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

### 6.3 CSRF Protection

Green includes built-in Cross-Site Request Forgery (CSRF) protection. It is enabled by default for all state-changing HTTP methods (`POST`, `PUT`, `PATCH`, `DELETE`).

#### How It Works
1. The `CsrfMiddleware` is registered globally in `public/index.php`.
2. Configuration lives in `config/csrf.php`.
3. Every form that uses a state-changing method must include a CSRF token pair.
4. The middleware validates the token on each request and throws a `TokenMismatchException` (HTTP 419) on failure.

#### Twig Forms
Add `{{ csrf_field() }}` inside every `POST` / `PUT` / `PATCH` / `DELETE` form:

```twig
<form method="POST" action="/posts">
    {{ csrf_field() }}

    <input type="text" name="title">
    <button type="submit">Save</button>
</form>
```

> **Important:** Do **not** add `{{ csrf_field() }}` to `GET` forms.

#### Using the Raw Token (Advanced)
If you need access to the token values (e.g. for AJAX), generate the token **once** and store it:

```twig
{% set csrf = csrf_token() %}
<meta name="csrf-id" content="{{ csrf.id }}">
<meta name="csrf-token" content="{{ csrf.token }}">
```

> **Warning:** Each call to `csrf_token()` generates a **new** token. Never call it multiple times for the same form — always store the result in a variable first.

#### AJAX / Fetch Requests
Read the token from a `<meta>` tag or a rendered attribute, then send it via headers:

```javascript
const csrfId    = document.querySelector('meta[name="csrf-id"]').content;
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

fetch('/posts', {
    method: 'POST',
    headers: {
        'X-CSRF-ID': csrfId,
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({ title: 'Hello World' }),
});
```

#### Configuration (`config/csrf.php`)

| Key            | Default           | Description                                  |
| :------------- | :---------------- | :------------------------------------------- |
| `enabled`      | `true`            | Toggle CSRF protection on/off                |
| `ttl`          | `1800`            | Token lifetime in seconds (30 min)           |
| `max_tokens`   | `50`              | Max active tokens per session                |
| `session_key`  | `_csrf_tokens`    | Session storage key                          |
| `id_input`     | `_csrf_id`        | Hidden input name for the token ID           |
| `token_input`  | `_csrf_token`     | Hidden input name for the token value        |
| `id_header`    | `X-CSRF-ID`       | Request header for AJAX token ID             |
| `token_header` | `X-CSRF-TOKEN`    | Request header for AJAX token value          |
| `except`       | `['/webhooks/*']` | Paths excluded from CSRF verification        |

#### Excluding Routes
Add path patterns to the `except` array in `config/csrf.php`. A trailing `*` matches any sub-path:

```php
'except' => [
    '/webhooks/*',
    '/api/public/*',
],
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

### 7.3 Debugging with `leaf()`

`leaf($value)` is Green Framework's native dump-and-die helper. It renders a formatted dump of any PHP value, displays the call site and runtime context, then terminates execution immediately.

```php
leaf($user);
```

#### Supported Values

`leaf()` supports scalars, arrays, objects, Green models, exceptions, resources, and nested structures. It detects repeated object references and marks them as circular instead of recursing forever.

Green models are rendered as `model` nodes with class, table, primary key, primary key value, and resolved attributes. Exceptions are rendered as `exception` nodes with message, code, file, line, and a limited trace.

#### Configuration

Publish the config file into an application:

```bash
php green publish:config leaf
```

This creates `config/leaf.php`:

```php
<?php

return [
    'max_depth' => 6,
    'max_items' => 100,
    'max_string_length' => 20000,
    'dark_theme' => true,
];
```

`leaf()` reads `config/leaf.php` automatically when the file exists. You can override the config file path with `GREEN_LEAF_CONFIG` or `LEAF_CONFIG`.

You can also configure it at runtime:

```php
leaf_config([
    'max_depth' => 6,
    'max_items' => 100,
]);
```

Or override individual values via environment:

```dotenv
GREEN_LEAF_DEPTH=6
GREEN_LEAF_ITEMS=100
GREEN_LEAF_STRING_LIMIT=20000
GREEN_LEAF_DARK=true
```

Defaults are conservative: depth `5`, items `50`, string length `10000`, dark theme enabled.

#### Example: `max_depth`

- The data has multiple nested levels:

```text
Level 1: data
Level 2: user
Level 3: profile
Level 4: address
```

#### Leaf Config Priority

1. Runtime Config (`leaf_config(...)`)
2. `config/leaf.php`
3. Environment Variables (`GREEN_LEAF_*`)
4. Internal Defaults

#### Best Practices

Use `leaf()` only during development or local debugging. Since it intentionally terminates the script and returns a debug response, it should not remain in committed application code paths.

Keep depth and item limits low for large model graphs, API payloads, and ORM-style relation trees. Raise limits only when investigating a specific structure.

Prefer dumping focused values instead of entire containers or application instances. Smaller dumps are faster, easier to read, and less likely to expose secrets.

Avoid dumping credentials, tokens, session payloads, or customer data in shared environments.

#### Performance Notes

The dumper never walks more than `max_depth` levels or `max_items` entries per collection/object. Strings are truncated at `max_string_length`. Repeated object references are tracked by `spl_object_id()` to prevent circular object graphs from exhausting memory.

Arrays that contain recursive references are still protected by the depth limit. PHP does not expose a stable, mutation-free array identity, so depth limits are the safe guard for recursive array structures.

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
| `csrf_token()`                 | `array`            | Generates a CSRF `{id, token}` pair.|
| `csrf_field()`                 | `string` (HTML)    | Outputs two hidden CSRF inputs.     |
| `connect()`                    | `Connect`          | Sends outgoing HTTP requests to external APIs. |
| `signal()`                     | `SignalDispatcher` | Registers listeners and emits application signals. |
| `authorizer()`                 | `Authorizer`       | Registers policies and checks abilities. |
| `cache()`                      | `CacheManager`     | Reads and writes cache values. |
| `route(name, params)`          | `string`           | Generates a URL for a named route. |
| `leaf(value)`                  | `never`            | Dumps a formatted value and terminates execution. |
| `leaf_config(options)`         | `void`             | Overrides `leaf()` runtime dump limits. |

---

## 🔧 9. Console Commands

Green provides a simple CLI interface for development and code generation.

### 📋 Available Commands

```bash
php green help               # Display help for a command
php green list               # List all available commands
php green serve              # Start development server

php green create:model Car             # Create a new model
php green create:controller Car        # Create a new controller
php green create:provider App          # Create a new service provider
php green create:authorizer Post       # Create a new authorizer class
php green create:policy Post           # Create a new policy class
php green create:event UserCreated     # Create a new event class
php green create:listener SendWelcome  # Create a new listener class
php green translation:clear            # Clear all cached translations
```

### Creating Service Providers

Use `create:provider` to generate an application service provider under `app/Providers`:

```bash
php green create:provider App
php green create:provider AppServiceProvider
```

Both examples create:

```text
app/Providers/AppServiceProvider.php
```

Generated providers extend `YasserElgammal\Green\Support\ServiceProvider` and include `register()` and `boot()` methods:

```php
namespace App\Providers;

use YasserElgammal\Green\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
```

Register the provider in `config/app.php`:

```php
'providers' => [
    \App\Providers\AppServiceProvider::class,
],
```

### Publishing Config Files

Use `publish:config` to copy framework config files into your application so you can customize them:

```bash
php green publish:config leaf
php green publish:config csrf
php green publish:config connect
php green publish:config drive
```

These commands publish the matching config files into the local `config/` directory. Run only the commands for the subsystems you want to customize.

If a config file has not been published, Green uses built-in defaults:

| Config | Built-in default |
| :--- | :--- |
| `leaf` | Uses conservative dump limits: depth `5`, items `50`, string length `10000`, dark theme enabled. |
| `csrf` | CSRF protection is enabled with a 30-minute token TTL, 50 max tokens, standard form inputs, and standard AJAX headers. |
| `connect` | Defines a `default` connection using the `symfony` driver, no base URL, timeout `10`, connect timeout `5`, and `Accept: application/json`. |
| `drive` | Defines a `local` disk using the `local` driver, rooted at `{BASE_PATH}/public` or the current working directory's `public` folder. |
| `cache` | Defines `file`, `array`, `database`, and `redis` cache stores with a configurable default store. |

Publishing config is therefore only required when you want to customize those defaults.

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

### Grammar Abstraction

Schema SQL is generated by database grammar classes instead of being hardcoded in `Blueprint`.

| Class | Role |
| :--- | :--- |
| `Grammar` | Base contract for schema SQL compilation. |
| `MySqlGrammar` | Compiles MySQL-compatible schema SQL. |
| `SqliteGrammar` | Compiles SQLite-compatible schema SQL. |
| `GrammarFactory` | Resolves the correct grammar from the active PDO driver. |

`Blueprint` collects operations such as columns, indexes, foreign keys, and drops, then delegates SQL generation to the selected grammar. This keeps migrations portable across supported database engines.
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

### Translation Caching & CLI

To optimize performance, Green caches parsed translations.

If you make modifications to your JSON translation files, you can easily clear the cache using the dedicated CLI command:

```bash
php green translation:clear
```

This will safely remove all cached translation files so that changes to your JSON strings are picked up immediately on the next request.

---

## 🔍 12. Include Query Language (IQL)

Green Core ships with a built-in **Include Query Language** that lets API consumers control exactly how relations are loaded — with limits, ordering, column selection, and filtering — all from a single string.

### 12.1 Quick Start

```php
// Simple (unchanged — fully backward compatible)
$table->include('comments,roles');

// Advanced — with constraints
$table->include('comments(limit:5,order:desc)');

// Nested — with operations at every level
$table->include('comments(limit:5,order:desc).author(select:id|name)');

// Mixed — simple + advanced in one call
$table->include('comments(limit:5).author(select:id|name),roles');
```

### 12.2 Syntax Reference

#### Basic Relations

```
include('comments')              → Load comments
include('comments,roles')        → Load comments and roles
include('comments.author')       → Load comments, then load author on each comment
```

#### Operations

Operations are placed inside parentheses after a relation name:

```
relation(operation:value)
relation(op1:value1,op2:value2)
```

#### Available Operations

| Operation | Syntax | Description |
|-----------|--------|-------------|
| **limit** | `limit:N` | Limit results to N rows |
| **offset** | `offset:N` | Skip first N rows |
| **order** | `order:asc` | Order by primary key ASC/DESC |
| | `order:column\|direction` | Order by specific column |
| **select** | `select:col1\|col2\|col3` | Select specific columns only |
| **filter** | `filter:column=value` | Filter by column value |
| **count** | `count` | Counts related rows. Generates `{relation}_count` attribute. |
| **exists** | `exists` | Checks if any related rows exist. Generates `{relation}_exists` (boolean). |
| **sum** | `sum:col` | Sums a numeric column. Generates `{relation}_sum_{col}` attribute. |
| **avg** | `avg:col` | Averages a numeric column. Generates `{relation}_avg_{col}` (float/null) attribute. |
| **min** | `min:col` | Minimum value of a column. Generates `{relation}_min_{col}` attribute. |
| **max** | `max:col` | Maximum value of a column. Generates `{relation}_max_{col}` attribute. |

> **Note:** The pipe `|` character is used as a separator within values because commas separate operations.

#### Examples

```php
// Get latest 5 comments
$table->include('comments(limit:5,order:desc)');

// Get comments with only id and content columns
$table->include('comments(select:id|content)');

// Get active comments ordered by creation date
$table->include('comments(filter:status=active,order:created_at|desc)');

// Get 10 comments with their authors (only id and name)
$table->include('comments(limit:10).author(select:id|name)');

// Pagination-like: skip 10, take 5
$table->include('comments(limit:5,offset:10)');

// Multiple relations, mixed syntax
$table->include('comments(limit:5,order:desc).author(select:id|name),roles');

// Dynamic aggregation with limits (mixed syntax)
$table->include('comments(limit:5,count)'); // computes comments_count and loads first 5 comments

// Check if likes exist, and calculate total price of orders
$table->include('likes(exists),orders(sum:price)');
```

#### ⚡ Aggregation-Only Optimization
If an include query contains **only** aggregation operations (e.g. `comments(count)` or `likes(exists),orders(sum:total)`) and no other constraints or child relations, the framework **skips loading the relation records entirely**. It only runs the highly optimized batch aggregation query and attaches the results directly to the parent models, which avoids unnecessary database memory and hydration overhead.

---

## 13. Connect: External HTTP APIs

Connect is Green's lightweight subsystem for making outgoing HTTP requests to external services such as payment gateways, messaging providers, CRMs, ERPs, shipping APIs, and webhooks.

Connect is separate from `Http\Request` and `Http\Response`. The `Http` namespace handles incoming requests to your application, while Connect handles outgoing requests from your application to third-party services.

### Configuration

Connect uses a plain PHP config file:

```txt
config/connect.php
```

Example:

```php
<?php

return [
    'default' => 'default',

    'connections' => [
        'default' => [
            'driver' => 'symfony',
            'base_url' => '',
            'timeout' => 10,
            'connect_timeout' => 5,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ],

        'payments' => [
            'driver' => 'symfony',
            'base_url' => $_ENV['PAYMENTS_BASE_URL'] ?? 'https://api.example.com',
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/json',
            ],
        ],
    ],
];
```

Recommended `.env` values:

```env
CONNECT_DEFAULT=default
CONNECT_CONFIG=config/connect.php
CONNECT_TIMEOUT=10
CONNECT_CONNECT_TIMEOUT=5

PAYMENTS_BASE_URL=https://api.example.com
PAYMENTS_TOKEN=
```

### Basic Usage

Use the `connect()` helper anywhere in your application code:

```php
$response = connect()->get('https://api.example.com/users');

if ($response->successful()) {
    $users = $response->json();
}
```

### Named Connections

Use named connections for services your application calls often:

```php
$response = connect()
    ->connection('payments')
    ->withToken($_ENV['PAYMENTS_TOKEN'])
    ->post('/charges', [
        'amount' => 1000,
        'currency' => 'usd',
    ]);
```

The final URL is built from the connection `base_url` plus the request path.

### Supported Methods

```php
connect()->get('/customers', ['page' => 1]);
connect()->post('/customers', ['name' => 'Green User']);
connect()->put('/customers/1', ['name' => 'Updated']);
connect()->patch('/customers/1', ['status' => 'active']);
connect()->delete('/customers/1');
```

### Headers and Authentication

```php
$response = connect()
    ->connection('crm')
    ->withHeaders([
        'X-Tenant' => 'green',
    ])
    ->withToken($token)
    ->acceptJson()
    ->get('/contacts');
```

For basic authentication:

```php
$response = connect()
    ->connection('erp')
    ->withBasicAuth($username, $password)
    ->get('/orders');
```

### Timeouts and Retries

```php
$response = connect()
    ->connection('shipping')
    ->timeout(10)
    ->connectTimeout(3)
    ->retry(3, 200)
    ->post('/labels', [
        'order_id' => 123,
    ]);
```

The second argument to `retry()` is the sleep time between attempts in milliseconds.

By default, retries are applied to transport-level failures such as connection errors and timeouts. To retry based on an HTTP response, pass a callback:

```php
$response = connect()
    ->connection('shipping')
    ->retry(3, 200, fn ($response, $exception) => $response?->serverError() ?? false)
    ->get('/health');
```

### Working with Responses

Connect returns a `ConnectResponse` object:

```php
$response = connect()->get('/health');

$response->status();       // 200
$response->body();         // raw response body
$response->json();         // decoded JSON array
$response->json('id');     // value from decoded JSON
$response->header('Date'); // response header
```

Status helpers:

```php
$response->ok();
$response->successful();
$response->redirect();
$response->clientError();
$response->serverError();
$response->failed();
```

HTTP `4xx` and `5xx` responses do not throw automatically. If you want failed responses to throw a `RequestException`, call:

```php
$response = connect()
    ->connection('payments')
    ->post('/charges', $data)
    ->throw();
```

Transport-level failures such as DNS errors, refused connections, and timeouts throw Connect exceptions immediately.

### Testing with Fakes

Connect includes a fake driver for tests:

```php
$fake = connect()->fake('payments');

$fake->respondJson(['id' => 'charge_123'], 201);

$response = connect()
    ->connection('payments')
    ->post('/charges', ['amount' => 1000]);

$fake->assertSent('POST', '/charges');
$fake->assertSentCount(1);
```

You can also assert that no external calls were made:

```php
$fake = connect()->fake();

// run code...

$fake->assertNothingSent();
```

### Explicit Manager Usage

For advanced setup, use the manager directly:

```php
$manager = $app->getConnectManager();

$manager->extend('custom', function (array $config) {
    return new CustomConnectDriver($config);
});
```

Connect follows Green's subsystem style: explicit managers, small drivers, no global service container, and no hidden dependency injection magic.

---

## 📝 14. Error Handling & Logger System

Green includes a centralized, zero-manual-intervention error system that automatically captures exceptions, warnings, and fatal errors.

### 14.1 Automatic Error Capture
The global `ExceptionHandler` catches all unhandled exceptions and normalizes them into a unified structure. These errors are automatically forwarded to the **Logger**. 

### 14.2 The Logger System
The `Logger` is a pluggable, driver-based system (e.g., file-based, Papertrail, etc.). It provides deduplication, rate limiting, and loop prevention to ensure execution safety.

You can also use the Logger manually to record events:

```php
use YasserElgammal\Green\Logging\Logger;

class PaymentService {
    public function __construct(private readonly Logger $logger) {}

    public function process() {
        $this->logger->info('Payment processing started', ['user_id' => 1]);
        
        try {
            // Logic...
        } catch (\Exception $e) {
            $this->logger->error('Payment failed', ['exception' => $e]);
        }
    }
}
```

---

## ⚡ 15. Cache Management

Green includes a unified cache subsystem built around `CacheManager` and `CacheDriverInterface`.

Supported drivers:

| Driver | Class |
| :--- | :--- |
| `array` | `ArrayDriver` |
| `file` | `FileDriver` |
| `database` | `DatabaseDriver` |
| `redis` | `RedisDriver` |

### 15.1 Configuration
Configure your default cache store in `.env`:
```env
CACHE_DRIVER=file
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```
Detailed store configuration is located in `config/cache.php`.

### 15.2 Usage
Use the global `cache()` helper or inject `CacheManager`.

```php
$value = cache()->get('key');

cache()->put('key', $value, 60);

$result = cache()->remember('key', 60, fn () => expensive_lookup());
```

Common operations are proxied to the default store:

```php
cache()->has('key');
cache()->forget('key');
cache()->flush();
```

### 15.3 Store Selection
Use `store()` to target a configured store by name:

```php
$value = cache()->store('redis')->get('key');
cache()->store('array')->put('preview', $data, 30);
```

### 15.4 Custom Drivers
Register custom drivers with `CacheManager::extend()`:

```php
cache()->extend('custom', function (array $config) {
    return new CustomCacheDriver($config);
});
```

The file driver clears PHP's file stat cache after deletion, so `has()` reflects a deleted key immediately after `forget()`.

---

## 📡 16. Events & Signals

The `SignalDispatcher` provides a small decoupled event system for application signals.

### 16.1 Listening to Signals
Register listeners with the global `signal()` helper:

```php
signal()->listen('user.created', function (array $payload) {
    $user = $payload['user'];
}, priority: 0);
```

Lower priority numbers run earlier. If a listener returns `false`, propagation stops and later listeners are not called.

```php
signal()->listen('user.created', function (array $payload) {
    return false;
});
```

### 16.2 Emitting Signals
Emit a signal with an optional payload array:

```php
signal()->emit('user.created', ['user' => $user]);
```

`emit()` returns an array containing listener return values.

### 16.3 Signal Generators
Use the console generators to create event and listener classes:

```bash
php green create:event UserCreated
php green create:listener SendWelcomeEmail
```

---

## 🛡️ 17. Authorization (Gate & Policies)

Green provides an authorization layer built around `Authorizer`, `Policy`, `PolicyAttribute`, and `ForbiddenException`.

### 17.1 Policies
Policies are classes that contain authorization logic for a specific model or resource.

```php
namespace App\Policies;

use App\Models\User;
use YasserElgammal\Green\Auth\Policy;

class UserPolicy extends Policy
{
    public function update(?array $actor, User $user): bool
    {
        return $actor && (int) $actor['id'] === (int) $user->id;
    }
}
```

Register policies during application boot, usually in an application service provider:

```php
authorizer()->policy(User::class, UserPolicy::class);
```

### 17.2 Inline Abilities
Use inline abilities for simple checks that do not need a policy class:

```php
authorizer()->define('update-user', fn ($actor, $subject) => true);
```

### 17.3 Enforcement
The `Authorizer` can check or enforce an ability:

```php
authorizer()->check('update', $user, $currentUser);      // true or false
authorizer()->authorize('update', $user, $currentUser);  // throws ForbiddenException when denied
```

The short form is useful when the policy can resolve the actor itself:

```php
authorizer()->authorize('update', $user);
```

### 17.4 Controller Enforcement
Routes can be authorized using `#[PolicyAttribute]` directly on controller methods. If authorization fails, Green throws a `ForbiddenException` before the controller method executes.

```php
use YasserElgammal\Green\Auth\PolicyAttribute;

class UserController {
    #[Route('PUT', '/users/{id}', name: 'users.update')]
    #[PolicyAttribute('update', 'id')]
    public function update(Request $request, int $id) {
        // ...
    }
}
```

The first attribute argument is the ability name. The second argument is the route parameter name or subject string passed to the `Authorizer`.

### 17.5 Authorization Generators
Use the console generators to create authorization classes:

```bash
php green create:authorizer User
php green create:policy User
```