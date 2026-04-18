<?php
/**
 * 应用配置
 */

return [
    'name' => 'MVC Framework',
    'timezone' => 'Asia/Shanghai',
    'debug' => getenv('APP_DEBUG') ?: true,
    'url' => getenv('APP_URL') ?: 'http://localhost',
];
