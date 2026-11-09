<?php

namespace PHPFramework\Middleware;

class CachePage {

    public function handle(): void {
        $key = $this->getKey();

        if (!$content = cache()->get($key)) {
            cache()->set($key, $this->getData($key), 86400);
        }
    }

    public function getData($key): string {
        return view($key, ['title' => $key], 'default');
    }

    public function getKey(): string {
        $key = request()->uri;
        $key = explode('/', $key);
        $key = $key[count($key) - 1];
        $key = explode('?', $key);
        $key = $key[0];

        return $key;
    }
}
