<?php

namespace App\Models;

use Core\Model;

/**
 * 购买交易记录模型
 * 记录所有商品购买交易
 */
class PurchaseTransaction extends Model
{
    protected string $table = 'purchase_transactions';
    protected string $primaryKey = 'id';

    /**
     * 生成唯一交易ID
     */
    public static function generateTransactionId(): string
    {
        return 'TXN-' . uniqid() . '-' . time();
    }

    /**
     * 记录购买交易
     */
    public function recordPurchase(
        int $userId,
        int $goodId,
        int $quantity,
        float $unitPrice,
        float $totalAmount,
        string $tradeType = 'BUY',
        ?string $externalRef = null
    ): int|string|false {
        $transactionId = self::generateTransactionId();

        return $this->insert([
            'user_id' => $userId,
            'good_id' => $goodId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
            'transaction_id' => $transactionId,
            'trade_type' => $tradeType,
            'status' => 'SUCCESS',
            'external_ref' => $externalRef,
            'verified_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 获取用户的购买历史
     */
    public function getUserHistory(int $userId, int $limit = 50, int $offset = 0): array
    {
        return $this->rawQuery(
            "SELECT pt.*, g.name as good_name, g.good_type
             FROM purchase_transactions pt
             LEFT JOIN goods g ON pt.good_id = g.id
             WHERE pt.user_id = :user_id
             ORDER BY pt.created_at DESC
             LIMIT :limit OFFSET :offset",
            [':user_id' => $userId, ':limit' => $limit, ':offset' => $offset]
        ) ?? [];
    }

    /**
     * 获取交易统计
     */
    public function getStats(int $userId): array
    {
        return $this->rawQueryOne(
            "SELECT 
                COUNT(*) as transaction_count,
                COUNT(DISTINCT DATE(created_at)) as purchase_days,
                SUM(total_amount) as total_spent,
                AVG(total_amount) as avg_spent,
                MAX(created_at) as last_purchase_date
             FROM purchase_transactions 
             WHERE user_id = :user_id",
            [':user_id' => $userId]
        ) ?? [
            'transaction_count' => 0,
            'purchase_days' => 0,
            'total_spent' => 0,
            'avg_spent' => 0,
            'last_purchase_date' => null
        ];
    }

    /**
     * 查询特定交易
     */
    public function findByTransactionId(string $transactionId): ?array
    {
        return $this->rawQueryOne(
            "SELECT * FROM purchase_transactions 
             WHERE transaction_id = :transaction_id",
            [':transaction_id' => $transactionId]
        );
    }

    /**
     * 查询外部参考
     */
    public function findByExternalRef(string $externalRef): ?array
    {
        return $this->rawQueryOne(
            "SELECT * FROM purchase_transactions 
             WHERE external_ref = :external_ref",
            [':external_ref' => $externalRef]
        );
    }

    /**
     * 获取时间范围内的交易
     */
    public function getTransactionsByDateRange(
        int $userId,
        string $startDate,
        string $endDate
    ): array {
        return $this->rawQuery(
            "SELECT pt.*, g.name as good_name
             FROM purchase_transactions pt
             LEFT JOIN goods g ON pt.good_id = g.id
             WHERE pt.user_id = :user_id
             AND DATE(pt.created_at) BETWEEN :start_date AND :end_date
             ORDER BY pt.created_at DESC",
            [
                ':user_id' => $userId,
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]
        ) ?? [];
    }

    /**
     * 获取商品销售统计
     */
    public function getGoodsSalesStats(int $goodId): array
    {
        return $this->rawQueryOne(
            "SELECT 
                COUNT(*) as sales_count,
                SUM(quantity) as total_quantity_sold,
                SUM(total_amount) as total_revenue,
                AVG(unit_price) as avg_price
             FROM purchase_transactions 
             WHERE good_id = :good_id AND status = 'SUCCESS'",
            [':good_id' => $goodId]
        ) ?? [
            'sales_count' => 0,
            'total_quantity_sold' => 0,
            'total_revenue' => 0,
            'avg_price' => 0
        ];
    }
}
