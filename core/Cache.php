<?php

namespace PHPFramework;

class Cache
{

    public function set($key, $data, $seconds = 3600): void
    {
        $content['data'] = serialize($data);
        $content['expires_at'] = time() + $seconds;
        $cache_key = md5($key);
        db()->insertCache($cache_key, $content['data'], time(),$content['expires_at']);
    }

    public function get($key, $default = null)
    {
        $cache_key = md5($key);
        $cache = db()->findOne('cache', $cache_key, 'cache_key');
        if($cache)
        {
            $cache['data'] = unserialize($cache['data']);
            if(time() <= $cache['expires_at'])
            {
                return $cache['data'];
            }
            db()->deleteCache($cache_key);
        }
        return $default;
    }

    public function remove($key): void
    {
        $cache_key = md5($key);
        if(db()->findOne('cache', $cache_key, 'cache_key')) db()->deleteCache($cache_key);
    }

    public function clear(): void
    {
        db()->clearCache();
    }
}
