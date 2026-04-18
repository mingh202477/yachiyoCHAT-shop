<?php
/**
 * 应用启动文件
 */

session_start();

// 定义常量
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public');

// 加载环境配置
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// 自动加载
spl_autoload_register(function ($class) {
    $namespace = explode('\\', $class);
    $prefix = array_shift($namespace);

    $paths = [
        'Core' => CORE_PATH,
        'App' => APP_PATH,
    ];

    if (!isset($paths[$prefix])) {
        return;
    }

    $file = $paths[$prefix] . '/' . implode('/', $namespace) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// 设置时区
date_default_timezone_set(
    (require CONFIG_PATH . '/app.php')['timezone'] ?? 'UTC'
);

// 错误处理
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if ((error_reporting() & $errno) === 0) {
        return;
    }
    
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

set_exception_handler(function (Throwable $exception) {
    if ((require CONFIG_PATH . '/app.php')['debug']) {
        echo "<pre>";
        echo "异常: " . $exception->getMessage() . "\n";
        echo "文件: " . $exception->getFile() . ":" . $exception->getLine() . "\n";
        echo "追踪:\n" . $exception->getTraceAsString();
        echo "</pre>";
    } else {
        http_response_code(500);
        echo "系统错误，请稍后重试";
    }
});
