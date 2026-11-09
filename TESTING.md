# Testing with PHPUnit

Install development dependencies with `composer install`. The project uses PHPUnit 9.6, with Composer dependency resolution constrained to PHP 8.0 so the test tooling remains compatible with the application's minimum PHP version.

## Commands

```bash
composer test
```

Runs the `unit` suite: configuration, requests, sessions, CSRF, helpers, validation, rendering, pagination, application registry, routing, and page-cache behavior. No database is required.

```bash
TEST_DB_DSN='mysql:host=127.0.0.1;port=3306' \
TEST_DB_USERNAME=root TEST_DB_PASSWORD=your-test-password \
composer test:all
```

Runs both the `unit` and `integration` suites. Use a disposable MySQL server and a database user allowed to create and drop databases. Every integration test creates a random `mvc_test_` database and drops it during PHPUnit teardown. The application environment file and the database named in the supplied DSN are not modified. Schema-importing fixtures refuse to run outside generated test databases.

Run an individual test or suite with PHPUnit's normal CLI:

```bash
vendor/bin/phpunit --testsuite integration
vendor/bin/phpunit tests/HttpTest.php
vendor/bin/phpunit --filter testInvalidCsrfTokensAreRejected
```

Database tests require the same `TEST_DB_*` environment settings even when selected individually. Missing settings fail clearly rather than silently skipping integration coverage.

## Docker

```bash
docker compose -p mvc-tests -f docker/compose.test.yaml up --build --abort-on-container-exit --exit-code-from tests
docker compose -p mvc-tests -f docker/compose.test.yaml down
```

The test stack has its own MySQL service with temporary storage. It runs the complete PHPUnit suite with Xdebug coverage. Install Composer dependencies in the checkout first because the source directory is mounted into the container. This stack does not use the application's Compose services or load its environment file.

## Coverage

```bash
XDEBUG_MODE=coverage TEST_DB_DSN='mysql:host=127.0.0.1;port=3306' \
TEST_DB_USERNAME=root TEST_DB_PASSWORD=your-test-password \
composer test:coverage
```

PHPUnit generates native text, Clover XML, and HTML coverage reports. The HTML report starts at [tests/.coverage/html/index.html](tests/.coverage/html/index.html); Clover output is [tests/.coverage/clover.xml](tests/.coverage/clover.xml). Generated reports and PHPUnit's result cache are ignored by Git.

The coverage filter includes all PHP under the framework, application, helpers, and migrations, including uncovered files. Xdebug also records actual execution in the HTTP server and migration CLI subprocesses. Their raw measurements are appended to the active PHPUnit test before teardown, using the same application filter. This measures redirects, request termination, and rendered error pages without replacing production behavior.

`composer test:coverage` requires exactly 100% executable-line coverage and fails if even one executable line is uncovered or the report is missing/empty. [tests/check_coverage.php](tests/check_coverage.php) enforces this in local runs and CI. The target is line coverage; branch/path coverage and JavaScript coverage are separate metrics. The production front controller, deployment configuration, third-party packages, and tests are outside the existing application PHP filter.

## Fixtures and isolation

[phpunit.xml](phpunit.xml) enables process isolation because the application uses global helpers, static services, route constants, and PHP sessions. PHPUnit converts PHP warnings, notices, and deprecations to test errors and treats warnings and risky tests as failures.

[FrameworkTestCase](tests/support/FrameworkTestCase.php) supplies exception assertions and session cleanup. [DatabaseTestCase](tests/support/DatabaseTestCase.php) owns disposable database creation and teardown. [HttpTest](tests/HttpTest.php) starts a local PHP server and temporary session directory in `setUp()` and cleans them up in `tearDown()`. Each HTTP test has its own server and database.

The CLI test runs a copy of the real migration entry point in a temporary directory with isolated environment settings and the real autoloader and migration files.

## Scope

| Test class | Main behaviors |
|---|---|
| ConfigTest | Environment parsing, normalized credentials, paths, DSN generation, invalid settings |
| RenderingTest | Escaped credentials, validation/output states, audit details, pagination boundaries, alerts and missing partials |
| CoverageGateTest | Rejection of empty, malformed, and incomplete coverage reports |
| MigrationFilesystemTest | Failure to enumerate migration files is reported without recording success |
| CoreTest | Requests, sessions, CSRF, authentication helpers, escaping, form helpers, validation, pagination, layouts |
| RoutingCacheTest | Controller/closure arguments, dispatch isolation, guard enforcement, cache keys and bypass behavior |
| CacheDatabaseTest | Atomic replacement, TTL refresh, expiration, false values, removal, clearing |
| MigrationsTest | Credential validation, collisions, hashing, history, repeat execution, rollback |
| DatabaseApplicationTest | CRUD, transactions, registration/login/logout, development consoles, audit logging, migration order |
| HttpTest | Status codes, redirects, CSRF, protected routes, cookies, registration/login/logout, disabled consoles, rendering, cache isolation |
| CliTest | Missing environment, invalid credentials, exit status, admin creation, password secrecy, repeat execution |

Browser JavaScript, external assets, Nginx/PHP-FPM deployment, the production front-controller exception handler, and PostgreSQL/SQLite execution remain outside these tests. The HTTP suite tests a refused database connection and verifies the resulting error log and response. MySQL is the integration-test backend.

[GitHub Actions](.github/workflows/tests.yml) runs PHPUnit with coverage on PHP 8.0 and 8.4 against MySQL 8 and uploads the coverage reports. Follow [LINTER.md](LINTER.md) when adding tests; use `*Test.php` classes and add them to the relevant suite in [phpunit.xml](phpunit.xml).
