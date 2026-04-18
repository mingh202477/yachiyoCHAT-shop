<?php

namespace Core;

class Database
{
    private static ?Database $instance = null;
    private $connection;

    private function __construct()
    {
        $this->connect();
    }

    /**
     * 获取数据库单例实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 连接到数据库
     */
    private function connect(): void
    {
        $config = require CONFIG_PATH . '/database.php';
        
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'],
            $config['port'],
            $config['database']
        );

        try {
            $this->connection = new \PDO(
                $dsn,
                $config['user'],
                $config['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (\PDOException $e) {
            die('数据库连接失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取数据库连接
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    /**
     * 执行查询
     */
    public function query(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            throw new \Exception('查询失败: ' . $e->getMessage());
        }
    }

    /**
     * 执行单条查询
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() ?: null;
        } catch (\PDOException $e) {
            throw new \Exception('查询失败: ' . $e->getMessage());
        }
    }

    /**
     * 执行插入/更新/删除操作
     */
    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw new \Exception('操作失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取最后插入的ID
     */
    public function lastInsertId(string $sequence = null): string
    {
        if ($sequence) {
            return $this->connection->lastInsertId($sequence);
        }
        return $this->connection->lastInsertId();
    }

    /**
     * 开始事务
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * 回滚事务
     */
    public function rollback(): void
    {
        $this->connection->rollBack();
    }
}
