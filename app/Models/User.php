<?php

namespace App\Models;

use Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    /**
     * 获取用户的所有文章
     */
    public function getPosts()
    {
        $sql = "SELECT p.* FROM posts p WHERE p.user_id = :user_id";
        return $this->rawQuery($sql, [':user_id' => $this->id ?? null]);
    }

    /**
     * 验证邮箱是否存在
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        $params = [':email' => $email];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }

        $result = $this->rawQueryOne($sql, $params);
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * 按邮箱查找用户
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy(['email' => $email]);
    }
}
