<?php

namespace Core;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取表名
     */
    protected function getTable(): string
    {
        if (!isset($this->table)) {
            $this->table = strtolower(class_basename($this)) . 's';
        }
        return $this->table;
    }

    /**
     * 查询所有记录
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->getTable()}";
        return $this->db->query($sql) ?? [];
    }

    /**
     * 查询单条记录
     */
    public function find(int|string $id): ?array
    {
        $sql = "SELECT * FROM {$this->getTable()} WHERE {$this->primaryKey} = :id";
        return $this->db->queryOne($sql, [':id' => $id]);
    }

    /**
     * 根据条件查询
     */
    public function findBy(array $where): ?array
    {
        $conditions = [];
        $params = [];

        foreach ($where as $key => $value) {
            $conditions[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }

        $sql = "SELECT * FROM {$this->getTable()} WHERE " . implode(' AND ', $conditions);
        return $this->db->queryOne($sql, $params);
    }

    /**
     * 根据条件查询多条
     */
    public function findAll(array $where = []): array
    {
        if (empty($where)) {
            return $this->all();
        }

        $conditions = [];
        $params = [];

        foreach ($where as $key => $value) {
            $conditions[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }

        $sql = "SELECT * FROM {$this->getTable()} WHERE " . implode(' AND ', $conditions);
        return $this->db->query($sql, $params) ?? [];
    }

    /**
     * 插入记录
     */
    public function insert(array $data): int|string|false
    {
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":{$f}", $fields);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->getTable(),
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $this->db->execute($sql, $data);
        
        return $this->db->lastInsertId();
    }

    /**
     * 更新记录
     */
    public function update(int|string $id, array $data): int
    {
        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            $updates[] = "{$key} = :{$key}";
            $params[":{$key}"] = $value;
        }

        $params[':id'] = $id;

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = :id",
            $this->getTable(),
            implode(', ', $updates),
            $this->primaryKey
        );

        return $this->db->execute($sql, $params);
    }

    /**
     * 删除记录
     */
    public function delete(int|string $id): int
    {
        $sql = "DELETE FROM {$this->getTable()} WHERE {$this->primaryKey} = :id";
        return $this->db->execute($sql, [':id' => $id]);
    }

    /**
     * 获取记录数
     */
    public function count(array $where = []): int
    {
        if (empty($where)) {
            $sql = "SELECT COUNT(*) as count FROM {$this->getTable()}";
            $result = $this->db->queryOne($sql);
        } else {
            $conditions = [];
            $params = [];

            foreach ($where as $key => $value) {
                $conditions[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }

            $sql = "SELECT COUNT(*) as count FROM {$this->getTable()} WHERE " . implode(' AND ', $conditions);
            $result = $this->db->queryOne($sql, $params);
        }

        return (int)($result['count'] ?? 0);
    }

    /**
     * 分页查询
     */
    public function paginate(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM {$this->getTable()} LIMIT :limit OFFSET :offset";
        
        $data = $this->db->query(
            $sql,
            [':limit' => $limit, ':offset' => $offset]
        ) ?? [];

        return [
            'page' => $page,
            'limit' => $limit,
            'total' => $this->count(),
            'data' => $data
        ];
    }

    /**
     * 原生SQL查询
     */
    protected function rawQuery(string $sql, array $params = []): ?array
    {
        return $this->db->query($sql, $params);
    }

    /**
     * 原生SQL单条查询
     */
    protected function rawQueryOne(string $sql, array $params = []): ?array
    {
        return $this->db->queryOne($sql, $params);
    }

    /**
     * 原生SQL执行
     */
    protected function rawExecute(string $sql, array $params = []): int
    {
        return $this->db->execute($sql, $params);
    }
}
