<?php

namespace App\Services;

use Core\HttpClient;

/**
 * 金币服务客户端
 * 与外部 CoinService 通信
 */
class CoinServiceClient
{
    private HttpClient $client;
    private string $apiUrl;

    public function __construct()
    {
        // 从环境变量读取外部服务 URL
        $this->apiUrl = getenv('COIN_SERVICE_URL') ?: 'http://localhost:8881';
        $this->client = new HttpClient($this->apiUrl);
    }

    /**
     * 获取用户金币余额
     * 
     * @param int $userId 用户ID
     * @param string $token 用户认证令牌
     * @return int 金币余额
     */
    public function getBalance(int $userId, string $token): int
    {
        try {
            $response = $this->client
                ->withBearerToken($token)
                ->post('/api/v2/coin/get');

            if ($response['code'] === '200' || $response['code'] === 200) {
                return (int)($response['data'] ?? 0);
            }

            throw new \Exception('获取金币余额失败: ' . ($response['message'] ?? '未知错误'));
        } catch (\Exception $e) {
            throw new \Exception('CoinService 错误: ' . $e->getMessage());
        }
    }

    /**
     * 执行金币交易
     * 
     * @param int $fromUserId 消费者用户ID
     * @param int $toUserId 接收者用户ID（可以是系统账户）
     * @param string $type 交易类型 (BUY, TIP 等)
     * @param float $amount 交易金额
     * @param string $token 用户认证令牌
     * @return array 交易结果
     */
    public function executeTransaction(
        int $fromUserId,
        int $toUserId,
        string $type,
        float $amount,
        string $token
    ): array {
        try {
            $payload = [
                'fromUserId' => $fromUserId,
                'toUserId' => $toUserId,
                'type' => $type,
                'amount' => $amount,
            ];

            $response = $this->client
                ->withBearerToken($token)
                ->post('/api/v2/coin/change', $payload);

            if ($response['code'] === '200' || $response['code'] === 200) {
                return [
                    'success' => true,
                    'data' => $response['data'] ?? null,
                    'message' => $response['message'] ?? '交易成功',
                ];
            }

            return [
                'success' => false,
                'data' => null,
                'message' => $response['message'] ?? '交易失败',
            ];
        } catch (\Exception $e) {
            throw new \Exception('CoinService 交易错误: ' . $e->getMessage());
        }
    }

    /**
     * 检查用户是否有足够的金币
     * 
     * @param int $userId 用户ID
     * @param float $requiredAmount 所需金币数
     * @param string $token 用户认证令牌
     * @return bool 是否足够
     */
    public function hasEnoughCoins(int $userId, float $requiredAmount, string $token): bool
    {
        try {
            $balance = $this->getBalance($userId, $token);
            return $balance >= $requiredAmount;
        } catch (\Exception $e) {
            throw new \Exception('无法验证金币余额: ' . $e->getMessage());
        }
    }

    /**
     * 设置超时时间
     */
    public function setTimeout(int $seconds): self
    {
        $this->client->setTimeout($seconds);
        return $this;
    }
}
