<?php

namespace App\Models;

use Core\Model;

/**
 * 商品模型
 */
class Good extends Model
{
    protected string $table = 'goods';
    protected string $primaryKey = 'id';

    /**
     * 获取待售商品（包含库存和非删除）
     */
    public function getAvailable(): array
    {
        return $this->rawQuery(
            "SELECT * FROM goods 
             WHERE stock > 0 
             AND deleted_at IS NULL
             ORDER BY created_at DESC"
        ) ?? [];
    }

    /**
     * 按类型获取商品
     */
    public function getByType(string $type): array
    {
        return $this->findAll([
            'good_type' => $type,
            'stock >' => 0,
            'deleted_at' => null,
        ]);
    }

    /**
     * 检查商品库存是否充足
     */
    public function hasStock(int $id, int $quantity = 1): bool
    {
        $good = $this->find($id);
        return $good && $good['stock'] >= $quantity && $good['deleted_at'] === null;
    }

    /**
     * 减少商品库存
     */
    public function decreaseStock(int $id, int $quantity): int
    {
        return $this->rawExecute(
            "UPDATE goods SET stock = stock - :quantity 
             WHERE id = :id AND stock >= :quantity",
            [':id' => $id, ':quantity' => $quantity]
        );
    }

    /**
     * 增加商品库存
     */
    public function increaseStock(int $id, int $quantity): int
    {
        return $this->execute(
            "UPDATE goods SET stock = stock + :quantity WHERE id = :id",
            [':id' => $id, ':quantity' => $quantity]
        );
    }

    /**
     * 搜索商品
     */
    public function search(string $keyword): array
    {
        return $this->rawQuery(
            "SELECT * FROM goods 
             WHERE (name ILIKE :keyword OR description ILIKE :keyword)
             AND stock > 0 
             AND deleted_at IS NULL
             ORDER BY created_at DESC",
            [':keyword' => "%{$keyword}%"]
        ) ?? [];
    }

    /**
     * 获取商品统计信息
     */
    public function getStats(): array
    {
        return $this->rawQueryOne(
            "SELECT 
                COUNT(*) as total,
                SUM(stock) as total_stock,
                COUNT(DISTINCT good_type) as type_count
             FROM goods
             WHERE deleted_at IS NULL"
        ) ?? ['total' => 0, 'total_stock' => 0, 'type_count' => 0];
    }
}
