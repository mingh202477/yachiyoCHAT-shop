<?php
require_once 'app/bootstrap.php';

try {
    $db = \Core\Database::getInstance();
    $result = $db->query("SELECT COUNT(*) as count FROM goods");
    echo "商品数量: " . $result[0]['count'] . "\n";

    if ($result[0]['count'] > 0) {
        $goods = $db->query("SELECT id, name, price, stock FROM goods LIMIT 5");
        echo "前5个商品:\n";
        foreach ($goods as $good) {
            echo "- ID: {$good['id']}, 名称: {$good['name']}, 价格: {$good['price']}, 库存: {$good['stock']}\n";
        }
    } else {
        echo "数据库中没有商品。请运行数据库脚本插入数据。\n";
    }
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>