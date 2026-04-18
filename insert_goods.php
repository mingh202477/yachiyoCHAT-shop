<?php
require_once 'app/bootstrap.php';

try {
    $db = \Core\Database::getInstance();

    // 插入示例商品
    $goods = [
        ['name' => '钻石剑', 'price' => 100.00, 'good_type' => 'WEAPON', 'description' => '锋利的钻石剑', 'stock' => 50],
        ['name' => '魔法药水', 'price' => 20.00, 'good_type' => 'POTION', 'description' => '恢复生命的药水', 'stock' => 100],
        ['name' => '金币袋', 'price' => 50.00, 'good_type' => 'CURRENCY', 'description' => '装满金币的袋子', 'stock' => 30],
        ['name' => '护甲', 'price' => 150.00, 'good_type' => 'ARMOR', 'description' => '坚固的护甲', 'stock' => 20],
        ['name' => '魔法书', 'price' => 75.00, 'good_type' => 'BOOK', 'description' => '学习魔法的书籍', 'stock' => 10],
    ];

    $stmt = $db->query("INSERT INTO goods (name, price, good_type, description, stock) VALUES (?, ?, ?, ?, ?)", [$good['name'], $good['price'], $good['good_type'], $good['description'], $good['stock']]);

    echo "示例商品已插入数据库。\n";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
?>