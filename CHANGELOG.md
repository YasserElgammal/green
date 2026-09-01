# Changelog

All notable changes to this project will be documented in this file.

## [2.5.0] - 2026-09-02

### Changed
- Invalidate sessions on logout, account deletion, and missing-user authentication instead of only clearing session data.
- Regenerate the session ID after login to improve session security.
- Expand session and authentication tests to verify session ID rotation and invalidation behavior.

## [2.4.0] - 2026-07-28

### Changed
- Sync API and web exception debug handling with the application configuration.

### Removed
- Remove the direct `firebase/php-jwt` dependency from `composer.json`.

## [2.3.1] - 2026-07-20

### Changed
- Streamline pagination and database queries across web and admin controllers.
- Expand controller test coverage for admin and post workflows.

## [2.3.0] - 2026-07-18

### Changed
- Switch application table relationships to Relation DTOs for an improved developer experience.
- Clarify the routing and pagination documentation.
- Update profile controller tests to resolve Drive through the application instance.

## [2.2.0] - 2026-07-12

### Added
- Add Arabic and English translations for the admin interface.

### Changed
- Localize admin views, shared partials, and pagination using translation functions.
- Enhance database querying documentation with fluent queries and advanced condition helpers.
- Update the application theme styles and main layout.

## [2.1.0] - 2026-06-29

### Added
- Add cache and database configuration files with matching environment variables.
- Add relation aggregation documentation and examples.

### Changed
- Expand the framework documentation for caching, database configuration, and relation aggregation.
- Update test configuration to use the application database settings.

## [2.0.0] - 2026-06-15

### Added
- Add native Green Framework service provider support.

### Changed
- Replace the old static provider boot flow with the framework-compatible service provider lifecycle.

## [1.9.0] - 2026-06-06

### Changed
- Update `green-core` dependency to `^1.8`.
- Update documentation for latest core features.

## [1.8.0] - 2026-05-31

### Added
- Admin dashboard, user management, and comment moderation system.
- Post controllers, comment liking functionality, and suite of unit tests.

## [1.7.0] - 2026-05-25

### Added
- Custom global exception handling and error response management with Twig templates.

## [1.6.0] - 2026-05-23

### Added
- Scaffold for API structure including base controllers, middleware, exception handlers, and CRUD test suite.
- Authentication system with JWT token support, user management, and API middleware.

## [1.5.2] - 2026-05-19

### Added
- Initialize validation language files.
- Base layout with responsive Bootstrap 5 styles and project development guidelines.

## [1.5.1] - 2026-05-17

### Added
- Localization support with `LocaleMiddleware`, multilingual language files, and initial frontend views.

## [1.5.0] - 2026-05-16

### Added
- Blog post system including CRUD views, controllers, database migrations, and file handling configuration.

### Changed
- Update `green-core` version.

## [1.4.0] - 2026-05-09

### Added
- CSRF protection with configuration, middleware, and Twig helpers, along with authentication and post views.

### Changed
- Add contribution guide and update core dependency to local path for development.

## [1.3.0] - 2026-05-02

### Added
- Database migrations, environment loading, and contribution guidelines to skeleton.

## [1.2.0] - 2026-04-11

### Added
- Migration classes for users, posts, comments, and likes.

### Changed
- Update `green-core` dependency version to `^1.2` in `composer.json`.
- Enhance documentation for migrations and schema builder.

### Removed
- Legacy SQL migration files.

## [1.1.1] - 2026-04-08

### Added
- Update schema to include likes table and seed data for comments.
- Tests for Auth, Post, and User controllers.

### Changed
- Update installation instructions in README for project setup.
- Organize API and web controllers.

### Removed
- Legacy `ApiControllerTest`.

## [1.1.0] - 2026-04-07

### Added
- Initial console kernel and entry point script.

## [1.0.0] - 2026-04-05

### Added
- Initialize Green framework and documentation.
