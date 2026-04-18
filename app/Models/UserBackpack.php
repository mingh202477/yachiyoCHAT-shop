<?php

namespace App\Models;

use Core\Model;

/**
 * 用户背包模型
 * 管理用户获得的物品
 */
class UserBackpack extends Model
{
    protected string $table = 'user_backpack';
    protected string $primaryKey = 'id';

    /**
     * 获取用户的所有物品
     */
    public function getUserItems(int $userId): array
    {
        return $this->rawQuery(
            "SELECT ub.*, g.name, g.description, g.price, g.good_type
             FROM user_backpack ub
             JOIN goods g ON ub.good_id = g.id
             WHERE ub.user_id = :user_id
             ORDER BY ub.acquired_at DESC",
            [':user_id' => $userId]
        ) ?? [];
    }

    /**
     * 获取用户背包中的某个物品数量
     */
    public function getItemQuantity(int $userId, int $goodId): int
    {
        $result = $this->rawQueryOne(
            "SELECT quantity FROM user_backpack 
             WHERE user_id = :user_id AND good_id = :good_id",
            [':user_id' => $userId, ':good_id' => $goodId]
        );
        return (int)($result['quantity'] ?? 0);
    }

    /**
     * 添加物品到背包
     */
    public function addItem(int $userId, int $goodId, int $quantity = 1): int
    {
        // 先检查是否已存在
        $existing = $this->rawQueryOne(
            "SELECT id FROM user_backpack 
             WHERE user_id = :user_id AND good_id = :good_id",
            [':user_id' => $userId, ':good_id' => $goodId]
        );

        if ($existing) {
            // 更新数量
            return $this->rawExecute(
                "UPDATE user_backpack 
                 SET quantity = quantity + :quantity
                 WHERE user_id = :user_id AND good_id = :good_id",
                [':user_id' => $userId, ':good_id' => $goodId, ':quantity' => $quantity]
            );
        } else {
            // 插入新记录
            return $this->insert([
                'user_id' => $userId,
                'good_id' => $goodId,
                'quantity' => $quantity,
            ]);
        }
    }

    /**
     * 减少背包中的物品数量
     */
    public function removeItem(int $userId, int $goodId, int $quantity = 1): int
    {
        // 减少数量，如果数量为0则删除
        return $this->rawExecute(
            "UPDATE user_backpack 
             SET quantity = quantity - :quantity
             WHERE user_id = :user_id AND good_id = :good_id AND quantity > :quantity",
            [':user_id' => $userId, ':good_id' => $goodId, ':quantity' => $quantity]
        );
    }

    /**
     * 删除背包中的物品
     */
    public function deleteItem(int $userId, int $goodId): int
    {
        return $this->rawExecute(
            "DELETE FROM user_backpack 
             WHERE user_id = :user_id AND good_id = :good_id",
            [':user_id' => $userId, ':good_id' => $goodId]
        );
    }

    /**
     * 获取用户背包统计
     */
    public function getUserBackpackStats(int $userId): array
    {
        return $this->rawQueryOne(
            "SELECT 
                COUNT(*) as item_count,
                SUM(quantity) as total_quantity
             FROM user_backpack 
             WHERE user_id = :user_id",
            [':user_id' => $userId]
        ) ?? ['item_count' => 0, 'total_quantity' => 0];
    }
}
