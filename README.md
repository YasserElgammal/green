# 🌿 Green Framework

**Green** is a lightweight, modern PHP framework built with PHP 8.2+ features, focusing on simplicity, speed, and developer experience. It leverages **PHP Attributes** for routing and follows a **Table Gateway** architecture.

---

## ⚡ Quick Start

### 1. Installation
```bash
composer create-project yasser-elgammal/green app-name
cd app-name
cp .env.example .env
php green serve
```

### 2. Define a Route
```php
class PostController {
    #[Route('GET', '/posts/{id}')]
    public function show(int $id) {
        return view('posts/show', ['id' => $id]);
    }
}
```

### 3. Database Access
```php
$posts = new PostTable();
$post  = $posts->fetchById(1);
```

---

## 📖 Master Documentation

The framework is divided into several powerful subsystems. Please refer to the **[Master Documentation](DOCUMENTATION.md)** for detailed guides on:

- **[Core Architecture](DOCUMENTATION.md#-1-core-architecture)**: Lifecycle and DI patterns.
- **[Routing & Middleware](DOCUMENTATION.md#-2-routing--middleware)**: Attribute-based routing and pipelines.
- **[Database & Relations](DOCUMENTATION.md#-3-database--table-gateway--models)**: Eager loading and Table Gateways.
- **[API Layer](DOCUMENTATION.md#-4-api-layer-transformers--pagination)**: Smart Transformers and Pagination.
- **[Logic & Validation](DOCUMENTATION.md#-5-logic-payloads--validation)**: Payload-based validation.
- **[Security & Sessions](DOCUMENTATION.md#-6-security--sessions)**: State management and hashing.
- **[Global Exception Handling](DOCUMENTATION.md#-7-exception-handling--debug-mode)**: Debug Mode and API errors.
- **[Helper Reference](DOCUMENTATION.md#-8-helper-reference-cheat-sheet)**: Glossary of global functions.
- **[Console Commands](DOCUMENTATION.md#-9-console-commands)**: CLI tools and generators.
- **[Migrations & Schema Builder](DOCUMENTATION.md#-10-migrations--schema-builder)**: Migration workflow, schema operations, dry-run mode, and safe mode.
- **[Translation & Localization](DOCUMENTATION.md#-11-translation--localization)**: Global helpers, multiple providers, caching, and pluralization.

---

## 🛠 Features at a Glance

- ✅ **PHP 8.2+ Attributes**: No more clunky routing files.
- ✅ **Table Gateway**: Clean separation of state (Model) and logic (Table).
- ✅ **Eager Loading**: Simple `include('relation')` to solve N+1.
- ✅ **Smart Transformers**: Nested API responses made easy.
- ✅ **Auto-Validation**: Type-hint payloads for instant validation.
- ✅ **Twig Templates**: Native Twig integration for clean views.
- ✅ **Debug UI**: Premium dark-mode error pages.

---

## 📜 License
Green Framework is open-sourced under the [MIT License](LICENSE).
