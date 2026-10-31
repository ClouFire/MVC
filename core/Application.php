<?php

namespace PHPFramework;

use function PHPSTORM_META\type;

class Application
{

    protected string $uri;
    public Request $request;
    public Response $response;
    public Router $router;
    public View $view;
    public Session $session;
    public Cache $cache;
    public Database $db;
    public static Application $app;
    protected array $container;

    public function __construct()
    {
        self::$app = $this;
        $this->uri = $_SERVER['REQUEST_URI'];
        $this->request = new Request($this->uri);
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
        $this->view = new View(Config::$layout);
        $this->session = new Session();
        $this->cache = new Cache();
        $this->db = Database::getInstance();
        $this->writeCsrfToken();
    }

    public function run(): void
    {
        echo $this->router->dispatch();
    }

    public function writeCsrfToken(): void
    {
        if(!session()->has('csrf_token'))
        {
            session()->set('csrf_token', bin2hex(random_bytes(32)));
        }
    }

    public function set($key, $value): void
    {
        $this->container[$key] = $value;
    }

    public function get($key, $default = null)
    {
        return $this->container[$key] ?? $default;
    }

}
