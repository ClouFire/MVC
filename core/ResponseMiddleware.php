<?php

namespace PHPFramework;

interface ResponseMiddleware {
    public function handle(callable $next): mixed;
}
