# Database migrations

Install Composer dependencies and initialize the existing database schema before running migrations. The Docker database service imports [schema.sql](docker/database/schema.sql) when its database is first initialized. This migration runner does not replace that initial schema setup.

Copy [.env.example](.env.example) to `.env`, configure the database connection, and fill in:

```dotenv
ADMIN_NAME=Administrator
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD="your-unique-password"
```

Use a unique password of 6 to 72 bytes. Passwords are hashed with PHP's `password_hash()` before insertion and are never printed. Do not commit `.env`.

Run from the project root:

```bash
php migrate.php
```

With Docker and the application container running:

```bash
docker compose -f docker/docker-compose.yaml exec app php migrate.php
```

The command applies pending migrations in filename order and records successful migrations in the `migrations` table. Run one migration process at a time. Repeating the command leaves completed migrations and existing credentials unchanged. Changing the environment credentials after a successful run does not reset the password or create another account.

The first migration creates the initial user. The current application has no roles or admin permissions, so this account uses the existing login flow and has the same permissions as other authenticated users. An existing name or email causes the migration to fail without updating the account; choose unused credentials and rerun. Missing or invalid credentials also fail without recording the migration.

After successful creation, the admin credentials can be removed from `.env`. Migration history contains only the filename and application timestamp.

## Adding a migration

Add a PHP file under [database/migrations](database/migrations) with a sortable, unique filename, returning an object that implements [Migration](core/Migration.php). Its `up(PDO $connection): void` method performs the change using the supplied connection. Use prepared statements for data values. Do not edit already applied migrations; add a new migration instead.

Data changes and the migration history entry run in one transaction. Some databases, including MySQL, implicitly commit schema changes such as `CREATE TABLE` and `ALTER TABLE`; those changes cannot be rolled back automatically and should be designed to support retry after a partial failure. This initial runner supports forward migrations only.

## Verification

Run `vendor/bin/phpunit tests/MigrationsTest.php` with `TEST_DB_DSN`, `TEST_DB_USERNAME`, and `TEST_DB_PASSWORD` pointing to a disposable MySQL server. PHPUnit creates and removes a dedicated database for the test. The cases cover credential validation, account collisions, password hashing, repeat execution, and rollback. See [TESTING.md](TESTING.md) for the full suite and CLI tests.
