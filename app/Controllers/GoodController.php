<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\Good;

/**
 * 商品控制器
 * 处理商品浏览、搜索、详情等API
 */
class GoodController extends Controller
{
    private Good $goodModel;

    public function __construct()
    {
        parent::__construct();
        $this->goodModel = new Good();
    }

    /**
     * 获取所有可用商品
     * GET /goods
     */
    public function index()
    {
        try {
            $type = $_GET['type'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);

            if ($type) {
                // 按类型查询
                $goods = $this->goodModel->getByType($type);
            } else {
                // 获取所有可用商品
                $goods = $this->goodModel->getAvailable();
            }

            // 分页处理
            $total = count($goods);
            $goods = array_slice($goods, ($page - 1) * $limit, $limit);

            $this->success([
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ],
                'goods' => $goods
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取单个商品详情
     * GET /goods/{id}
     */
    public function show(int $id)
    {
        try {
            $good = $this->goodModel->find($id);

            if (!$good) {
                return $this->error('商品不存在', 404);
            }

            if ($good['deleted_at'] !== null) {
                return $this->error('商品已下架', 410);
            }

            $this->json([
                'success' => true,
                'message' => '成功',
                'detail' => $good
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 搜索商品
     * GET /goods/search?keyword=xxx
     */
    public function search()
    {
        try {
            $keyword = $_GET['keyword'] ?? '';

            if (!$keyword || strlen($keyword) < 2) {
                return $this->error('搜索关键词至少2个字符', 400);
            }

            $goods = $this->goodModel->search($keyword);

            $this->success([
                'keyword' => $keyword,
                'count' => count($goods),
                'goods' => $goods
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取商品统计信息
     * GET /goods/stats
     */
    public function stats()
    {
        try {
            $stats = $this->goodModel->getStats();
            $this->success($stats);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取商品类型列表
     * GET /goods/types
     */
    public function types()
    {
        try {
            $types = $this->goodModel->rawQuery(
                "SELECT DISTINCT good_type FROM goods WHERE deleted_at IS NULL ORDER BY good_type"
            ) ?? [];

            $this->success([
                'types' => array_column($types, 'good_type')
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
