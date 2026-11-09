<?php

namespace PHPFramework;

interface Migration {
    public function up(\PDO $connection): void;
}
