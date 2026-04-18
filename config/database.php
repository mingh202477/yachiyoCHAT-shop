<?php
/**
 * 数据库配置
 */

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: 5432,
    'database' => getenv('DB_NAME') ?: 'mvc_app',
    'user' => getenv('DB_USER') ?: 'postgres',
    'password' => getenv('DB_PASSWORD') ?: 'password',
];
