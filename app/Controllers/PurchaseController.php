<?php

namespace App\Controllers;

use Core\Controller;
use App\Services\PurchaseService;
use App\Models\UserBackpack;

/**
 * 购买控制器
 * 处理商品购买、背包、购买历史等API
 */
class PurchaseController extends Controller
{
    private PurchaseService $purchaseService;
    private UserBackpack $backpackModel;

    public function __construct()
    {
        parent::__construct();
        $this->purchaseService = new PurchaseService();
        $this->backpackModel = new UserBackpack();
    }

    /**
     * 购买商品
     * POST /purchase
     * 
     * 请求体：
     * {
     *     "good_id": 1,
     *     "quantity": 1,
     *     "timeout": 6
     * }
     */
    public function store()
    {
        try {
            // 从 Header 获取用户信息
            $userId = $this->getUserIdFromHeader();
            $token = $this->getTokenFromHeader();

            if (!$userId || !$token) {
                return $this->error('未授权：缺少用户认证信息', 401);
            }

            // 解析请求体
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                return $this->error('请提交有效的JSON数据', 400);
            }

            // 参数验证
            if (!isset($data['good_id']) || !isset($data['quantity'])) {
                return $this->error('缺少必需参数: good_id, quantity', 400);
            }

            $goodId = (int)$data['good_id'];
            $quantity = (int)$data['quantity'];
            $timeout = (int)($data['timeout'] ?? 6);

            // 业务逻辑：执行购买
            $result = $this->purchaseService->purchase($userId, $goodId, $quantity, $token, $timeout);

            if ($result['success']) {
                $this->json($result, 200);
            } else {
                $this->json($result, $result['code']);
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * 获取用户背包
     * GET /backpack
     */
    public function getBackpack()
    {
        try {
            $userId = $this->getUserIdFromHeader();

            if (!$userId) {
                return $this->error('未授权：缺少用户ID', 401);
            }

            $result = $this->purchaseService->getUserBackpack($userId);
            $this->json($result, $result['code']);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * 获取背包中的特定物品
     * GET /backpack/{goodId}
     */
    public function getBackpackItem(int $goodId)
    {
        try {
            $userId = $this->getUserIdFromHeader();

            if (!$userId) {
                return $this->error('未授权：缺少用户ID', 401);
            }

            $quantity = $this->backpackModel->getItemQuantity($userId, $goodId);

            $this->success([
                'good_id' => $goodId,
                'quantity' => $quantity
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取购买历史
     * GET /purchase-history?page=1&limit=20
     */
    public function getHistory()
    {
        try {
            $userId = $this->getUserIdFromHeader();

            if (!$userId) {
                return $this->error('未授权：缺少用户ID', 401);
            }

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);

            $result = $this->purchaseService->getPurchaseHistory($userId, $page, $limit);
            $this->json($result, $result['code']);
        } catch (\Exception $e) {
            $this->error($e->getMessage(), 500);
        }
    }

    /**
     * 从 HTTP Header 获取用户ID
     * YachiyoServiceCloud 系统会在 X-User-Id 中传递用户ID
     */
    private function getUserIdFromHeader(): ?int
    {
        // 从自定义 Header 获取（由网关服务设置）
        $userId = $_SERVER['HTTP_X_USER_ID'] ?? null;
        
        if (!$userId) {
            // 兼容：从 Cookie 或其他方式获取
            $userId = $_COOKIE['user_id'] ?? null;
        }

        return $userId ? (int)$userId : null;
    }

    /**
     * 从 HTTP Header 获取 Token
     */
    private function getTokenFromHeader(): ?string
    {
        // 从 Authorization Header 获取 Bearer Token
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        
        if (!$authHeader) {
            // 兼容：从 X-Auth-Token Header 获取
            $authHeader = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? null;
        }

        if ($authHeader && preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
