<?php

if (!preg_match('/(?:^|;)dbname=mvc_test_[a-f0-9]{16}(?:;|$)/', getenv('TEST_DB_DSN') ?: '')) {
    throw new RuntimeException('Schema tests must run through PHPUnit database fixtures in an isolated test database.');
}

$schema = file_get_contents(dirname(__DIR__, 2) . '/docker/database/schema.sql');
$schema = preg_replace('/CREATE DATABASE.*?;\s*USE .*?;/s', '', $schema);

\PHPFramework\Database::connection()->exec($schema);
