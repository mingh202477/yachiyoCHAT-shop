<?php

namespace App\Services;

use App\Models\Good;
use App\Models\UserBackpack;
use App\Models\PurchaseTransaction;
use Core\Database;

/**
 * 商品购买服务
 * 核心购买流程：验证 -> 检查库存 -> 检查金币 -> 扣费 -> 物品入库 -> 记录交易
 */
class PurchaseService
{
    private Good $goodModel;
    private UserBackpack $backpackModel;
    private PurchaseTransaction $transactionModel;
    private CoinServiceClient $coinService;
    private Database $db;

    public function __construct()
    {
        $this->goodModel = new Good();
        $this->backpackModel = new UserBackpack();
        $this->transactionModel = new PurchaseTransaction();
        $this->coinService = new CoinServiceClient();
        $this->db = Database::getInstance();
    }

    /**
     * 用户购买商品
     * 完整的购买流程（2-6秒超时限制）
     */
    public function purchase(
        int $userId,
        int $goodId,
        int $quantity,
        string $token,
        ?int $timeoutSeconds = 6
    ): array {
        $startTime = microtime(true);

        try {
            // ==================== 1. 初始验证 ====================
            if ($userId <= 0 || $goodId <= 0 || $quantity <= 0) {
                return $this->errorResponse('参数无效', 400);
            }

            // (耗时: < 1秒)
            if (!$token || strlen($token) < 10) {
                return $this->errorResponse('认证令牌无效', 401);
            }

            // ==================== 2. 获取商品信息 ====================
            // (耗时: < 1秒)
            $good = $this->goodModel->find($goodId);

            if (!$good) {
                return $this->errorResponse('商品不存在', 404);
            }

            if ($good['deleted_at'] !== null) {
                return $this->errorResponse('商品已下架', 410);
            }

            $elapsedTime = microtime(true) - $startTime;
            if ($elapsedTime > $timeoutSeconds) {
                return $this->errorResponse('请求超时', 408);
            }

            // ==================== 3. 检查库存 ====================
            // (耗时: < 1秒)
            if (!$this->goodModel->hasStock($goodId, $quantity)) {
                return $this->errorResponse('库存不足', 400, [
                    'available_stock' => $good['stock'] ?? 0,
                    'requested_quantity' => $quantity
                ]);
            }

            $totalAmount = bcmul($good['price'], $quantity, 2);
            $elapsedTime = microtime(true) - $startTime;
            if ($elapsedTime > $timeoutSeconds) {
                return $this->errorResponse('库存检查超时', 408);
            }

            // ==================== 4. 检查用户金币余额 ====================
            // (耗时: 2-5秒，外部服务调用)
            try {
                $this->coinService->setTimeout(3); // 金币服务超时 3秒
                
                if (!$this->coinService->hasEnoughCoins($userId, $totalAmount, $token)) {
                    return $this->errorResponse('金币不足', 402, [
                        'required_coins' => $totalAmount,
                        'unit_price' => $good['price']
                    ]);
                }
            } catch (\Exception $e) {
                return $this->errorResponse('无法验证金币: ' . $e->getMessage(), 503);
            }

            $elapsedTime = microtime(true) - $startTime;
            if ($elapsedTime > $timeoutSeconds) {
                return $this->errorResponse('金币验证超时', 408);
            }

            // ==================== 5. 开始事务处理 ====================
            try {
                $this->db->beginTransaction();

                // 减少商品库存
                $stockReduced = $this->goodModel->decreaseStock($goodId, $quantity);
                if ($stockReduced === 0) {
                    $this->db->rollback();
                    return $this->errorResponse('库存更新失败', 500);
                }

                // ==================== 6. 扣除用户金币 ====================
                // (耗时: 1-3秒)
                try {
                    $coinResult = $this->coinService->executeTransaction(
                        $userId,                    // 消费者
                        1,                          // 系统账户（默认为1）
                        'BUY',                      // 交易类型
                        $totalAmount,               // 金额
                        $token
                    );

                    if (!$coinResult['success']) {
                        $this->db->rollback();
                        return $this->errorResponse('金币扣除失败: ' . $coinResult['message'], 402);
                    }
                } catch (\Exception $e) {
                    $this->db->rollback();
                    return $this->errorResponse('金币扣除异常: ' . $e->getMessage(), 503);
                }

                // ==================== 7. 物品添加到背包 ====================
                $backpackId = $this->backpackModel->addItem($userId, $goodId, $quantity);

                // ==================== 8. 记录交易 ====================
                $transactionId = $this->transactionModel->recordPurchase(
                    $userId,
                    $goodId,
                    $quantity,
                    $good['price'],
                    $totalAmount,
                    'BUY',
                    $coinResult['data']['transactionId'] ?? null
                );

                $this->db->commit();

                $elapsedTime = microtime(true) - $startTime;

                return [
                    'success' => true,
                    'message' => '购买成功',
                    'code' => 200,
                    'data' => [
                        'transaction_id' => $transactionId,
                        'good_id' => $goodId,
                        'good_name' => $good['name'],
                        'quantity' => $quantity,
                        'unit_price' => $good['price'],
                        'total_amount' => $totalAmount,
                        'backpack_id' => $backpackId,
                        'executed_time' => round($elapsedTime, 3) . 's'
                    ]
                ];
            } catch (\Exception $e) {
                try {
                    $this->db->rollback();
                } catch (\Exception $rollbackError) {
                }
                return $this->errorResponse('购买处理异常: ' . $e->getMessage(), 500);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('未知错误: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取用户背包
     */
    public function getUserBackpack(int $userId): array
    {
        try {
            $items = $this->backpackModel->getUserItems($userId);
            $stats = $this->backpackModel->getUserBackpackStats($userId);

            return [
                'success' => true,
                'message' => '获取成功',
                'code' => 200,
                'data' => [
                    'items' => $items,
                    'stats' => $stats
                ]
            ];
        } catch (\Exception $e) {
            return $this->errorResponse('获取背包失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取购买历史
     */
    public function getPurchaseHistory(int $userId, int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;
            $transactions = $this->transactionModel->getUserHistory($userId, $limit, $offset);
            $stats = $this->transactionModel->getStats($userId);

            return [
                'success' => true,
                'message' => '获取成功',
                'code' => 200,
                'data' => [
                    'transactions' => $transactions,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                    ],
                    'stats' => $stats
                ]
            ];
        } catch (\Exception $e) {
            return $this->errorResponse('获取购买历史失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 错误响应格式
     */
    private function errorResponse(string $message, int $code, ?array $extraData = null): array
    {
        $response = [
            'success' => false,
            'message' => $message,
            'code' => $code,
        ];

        if ($extraData) {
            $response['data'] = $extraData;
        }

        return $response;
    }
}
