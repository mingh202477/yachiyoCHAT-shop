<?php

namespace Core;

class View
{
    private string $viewPath = APP_PATH . '/Views';
    private array $data = [];

    /**
     * 设置视图变量
     */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * 渲染视图
     */
    public function render(string $view, array $data = []): void
    {
        $this->data = array_merge($this->data, $data);
        extract($this->data);

        $viewFile = $this->viewPath . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new \Exception("视图文件未找到: {$viewFile}");
        }

        include $viewFile;
    }

    /**
     * 输出HTML安全的变量
     */
    public function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * 引入局部视图
     */
    public function include(string $view, array $data = []): void
    {
        $this->render($view, $data);
    }
}
