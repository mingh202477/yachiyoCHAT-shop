<?php

namespace App\Controllers;

use Core\Controller;
use App\Models\User;

class UserController extends Controller
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }

    /**
     * 获取用户列表
     */
    public function index()
    {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;

            $result = $this->userModel->paginate($page, $limit);
            
            $this->success([
                'pagination' => [
                    'page' => $result['page'],
                    'limit' => $result['limit'],
                    'total' => $result['total'],
                    'pages' => ceil($result['total'] / $result['limit'])
                ],
                'users' => $result['data']
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 获取单个用户
     */
    public function show(int $id)
    {
        try {
            $user = $this->userModel->find($id);

            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $this->success($user);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 创建用户
     */
    public function store()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                return $this->error('请提交有效的JSON数据', 400);
            }

            // 验证
            if (!isset($data['name']) || !isset($data['email'])) {
                return $this->error('名称和邮箱为必填项', 400);
            }

            if ($this->userModel->emailExists($data['email'])) {
                return $this->error('邮箱已被使用', 400);
            }

            $userId = $this->userModel->insert([
                'name' => $data['name'],
                'email' => $data['email'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $user = $this->userModel->find($userId);
            $this->success($user, '用户创建成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 更新用户
     */
    public function update(int $id)
    {
        try {
            $user = $this->userModel->find($id);

            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                return $this->error('请提交有效的JSON数据', 400);
            }

            // 检查邮箱是否被其他用户使用
            if (isset($data['email']) && $data['email'] !== $user['email']) {
                if ($this->userModel->emailExists($data['email'], $id)) {
                    return $this->error('邮箱已被使用', 400);
                }
            }

            $updateData = [];
            foreach (['name', 'email'] as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }

            $updateData['updated_at'] = date('Y-m-d H:i:s');

            $this->userModel->update($id, $updateData);

            $user = $this->userModel->find($id);
            $this->success($user, '用户更新成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 删除用户
     */
    public function destroy(int $id)
    {
        try {
            $user = $this->userModel->find($id);

            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $this->userModel->delete($id);
            $this->success([], '用户删除成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
