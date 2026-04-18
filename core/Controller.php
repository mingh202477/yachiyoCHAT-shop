<?php

namespace Core;

class Controller
{
    protected View $view;

    public function __construct()
    {
        $this->view = new View();
    }

    /**
     * 返回JSON响应
     */
    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 返回成功响应
     */
    protected function success(array $data = [], string $message = '成功'): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * 返回错误响应
     */
    protected function error(string $message = '错误', int $code = 400): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'code' => $code
        ], $code);
    }

    /**
     * 渲染视图
     */
    protected function render(string $view, array $data = []): void
    {
        $this->view->render($view, $data);
    }

    /**
     * 重定向
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit();
    }
}
