<?php

namespace Core;

class Router
{
    protected array $routes = [];
    protected string $currentController;
    protected string $currentAction;
    protected array $currentParams = [];

    /**
     * 注册GET路由
     */
    public function get(string $route, string $controller): self
    {
        $this->routes['GET'][$route] = $controller;
        return $this;
    }

    /**
     * 注册POST路由
     */
    public function post(string $route, string $controller): self
    {
        $this->routes['POST'][$route] = $controller;
        return $this;
    }

    /**
     * 注册PUT路由
     */
    public function put(string $route, string $controller): self
    {
        $this->routes['PUT'][$route] = $controller;
        return $this;
    }

    /**
     * 注册DELETE路由
     */
    public function delete(string $route, string $controller): self
    {
        $this->routes['DELETE'][$route] = $controller;
        return $this;
    }

    /**
     * 分发请求
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->parsePath();

        if (!isset($this->routes[$method])) {
            $this->sendError(405, '方法不允许');
            return;
        }

        $route = $this->findRoute($path, $this->routes[$method]);

        if ($route === null) {
            $this->sendError(404, '页面未找到');
            return;
        }

        [$controller, $action] = $route;
        $this->callController($controller, $action);
    }

    /**
     * 获取请求路径
     */
    private function parsePath(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        
        if ($basePath !== '/' && strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }

        return '/' . trim($path, '/');
    }

    /**
     * 查找匹配的路由
     */
    private function findRoute(string $path, array $routes): ?array
    {
        // 精确匹配
        if (isset($routes[$path])) {
            return $this->parseController($routes[$path]);
        }

        // 参数匹配
        foreach ($routes as $route => $target) {
            $pattern = $this->routeToRegex($route);
            if (preg_match($pattern, $path, $matches)) {
                $this->currentParams = array_slice($matches, 1);
                return $this->parseController($target);
            }
        }

        return null;
    }

    /**
     * 路由转换为正则表达式
     */
    private function routeToRegex(string $route): string
    {
        $route = preg_replace('/\{(\w+)\}/', '([^/]+)', $route);
        return '#^' . $route . '$#';
    }

    /**
     * 解析控制器字符串
     */
    private function parseController(string $target): array
    {
        $parts = explode('@', $target);
        $controller = 'App\\Controllers\\' . $parts[0];
        $action = $parts[1] ?? 'index';
        
        return [$controller, $action];
    }

    /**
     * 调用控制器
     */
    private function callController(string $controller, string $action): void
    {
        if (!class_exists($controller)) {
            $this->sendError(500, '控制器不存在: ' . $controller);
            return;
        }

        $instance = new $controller();

        if (!method_exists($instance, $action)) {
            $this->sendError(500, '方法不存在: ' . $action);
            return;
        }

        call_user_func_array([$instance, $action], $this->currentParams);
    }

    /**
     * 发送错误响应
     */
    private function sendError(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'code' => $code,
            'message' => $message
        ]);
    }
}
