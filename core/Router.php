<?php

namespace PHPFramework;

class Router {
    protected Request $request;
    protected Response $response;
    protected array $routes = [];
    protected array $routeParams = [];

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    public function add($path, $callback, $method): self {
        $path = trim($path, '/');

        if (is_array($method)) {
            $method = array_map('strtoupper', $method);
        } else {
            $method = [strtoupper($method)];
        }

        $this->routes[] = [
            'path' => "/{$path}",
            'callback' => $callback,
            'method' => $method,
            'middleware' => [],
            'checkCsrfToken' => true,
        ];

        return $this;
    }

    public function getRoutes(): array {
        return $this->routes;
    }

    public function get($path, $callback): self {
        return $this->add($path, $callback, ['GET']);
    }

    public function post($path, $callback): self {
        return $this->add($path, $callback, ['POST']);
    }

    public function dispatch() {
        $this->routeParams = [];
        $path = $this->request->getPath();
        $route = $this->matchRoute($path);

        if (false === $route) {
            abort();
        } else {
            $middlewareStack = [];

            foreach ($route['middleware'] as $middleware) {
                $middlewareClass = MIDDLEWARE[$middleware] ?? null;

                if ($middlewareClass === null) {
                    throw new \RuntimeException("Unknown middleware: {$middleware}");
                }

                $instance = new $middlewareClass();

                if ($instance instanceof ResponseMiddleware) {
                    $middlewareStack[] = $instance;
                } else {
                    $instance->handle();
                }
            }

            $next = function () use ($route) {
                $callback = $route['callback'];

                if (is_array($callback) && is_string($callback[0])) {
                    $callback[0] = new $callback[0]();
                }

                return call_user_func_array($callback, array_values($this->routeParams));
            };

            foreach (array_reverse($middlewareStack) as $middleware) {
                $handler = $next;
                $next = fn() => $middleware->handle($handler);
            }

            return $next();
        }
    }

    protected function matchRoute($path): mixed {
        foreach ($this->routes as $route) {
            if (preg_match("#^{$route['path']}$#", "/{$path}", $matches)
                && in_array($this->request->getMethod(), $route['method'])) {
                if (request()->isPost()) {
                    if ($route['checkCsrfToken'] && !$this->checkCsrf()) {
                        if (request()->isAjax()) {
                            echo json_encode([
                                'status' => 'Error',
                                'data' => 'Security error',
                            ]);

                            die;
                        } else {
                            abort('Page expired', 419);
                        }
                    }
                }

                foreach ($matches as $key => $match) {
                    if (is_string($key)) {
                        $this->routeParams[$key] = $match;
                    }
                }

                return $route;
            }
        }

        return false;
    }

    public function checkCsrf(): bool {
        return request()->post('csrf_token') && request()->post('csrf_token') == session()->get('csrf_token');
    }

    public function disableCsrfToken(): self {
        $this->routes[array_key_last($this->routes)]['checkCsrfToken'] = false;

        return $this;
    }

    public function middleware(array $middleware): self {
        $this->routes[array_key_last($this->routes)]['middleware'] = $middleware;

        return $this;
    }
}
