<?php
/**
 * 应用路由配置
 */

require_once __DIR__ . '/bootstrap.php';

$router = new Core\Router();

// ==================== 默认路由 ====================
$router->get('/', 'HomeController@index');

// ==================== 用户相关路由 ====================
$router->get('/users', 'UserController@index');
$router->get('/users/{id}', 'UserController@show');
$router->post('/users', 'UserController@store');
$router->put('/users/{id}', 'UserController@update');
$router->delete('/users/{id}', 'UserController@destroy');

// ==================== 商品相关路由 ====================
$router->get('/goods', 'GoodController@index');              // 获取商品列表
$router->get('/goods/{id}', 'GoodController@show');          // 获取商品详情
$router->get('/goods/search', 'GoodController@search');      // 搜索商品
$router->get('/goods-stats', 'GoodController@stats');        // 获取商品统计
$router->get('/goods-types', 'GoodController@types');        // 获取商品类型

// ==================== 购买/背包相关路由 ====================
$router->post('/purchase', 'PurchaseController@store');               // 购买商品
$router->get('/backpack', 'PurchaseController@getBackpack');         // 获取用户背包
$router->get('/backpack/{id}', 'PurchaseController@getBackpackItem'); // 获取背包中的物品
$router->get('/purchase-history', 'PurchaseController@getHistory');   // 获取购买历史

// 分发请求
$router->dispatch();
