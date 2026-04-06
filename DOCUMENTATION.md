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
