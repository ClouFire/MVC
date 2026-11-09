<?php

namespace PHPFramework;

class Cache {

    public function set($key, $data, $seconds = 3600): void {
        $content = [];
        $content['data'] = serialize($data);
        $content['expires_at'] = time() + $seconds;
        $cacheKey = md5($key);

        db()->insertCache($cacheKey, $content['data'], time(), $content['expires_at']);
    }

    public function get($key, $default = null) {
        $cacheKey = md5($key);
        $cache = db()->findOne('cache', $cacheKey, 'cache_key');

        if ($cache) {
            $cache['data'] = unserialize($cache['data']);

            if (time() <= $cache['expires_at']) {
                return $cache['data'];
            }

            db()->deleteCache($cacheKey);
        }

        return $default;
    }

    public function remove($key): void {
        $cacheKey = md5($key);

        if (db()->findOne('cache', $cacheKey, 'cache_key')) {
            db()->deleteCache($cacheKey);
        }
    }

    public function clear(): void {
        db()->clearCache();
    }
}
