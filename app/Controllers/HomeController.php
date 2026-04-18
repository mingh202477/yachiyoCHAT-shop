<?php

namespace App\Controllers;

use Core\Controller;

class HomeController extends Controller
{
    /**
     * 首页
     */
    public function index()
    {
        // 返回前端HTML页面
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商店系统</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>商店系统</h1>
        <nav>
            <button id="goods-btn">商品列表</button>
            <button id="backpack-btn">我的背包</button>
            <button id="history-btn">购买历史</button>
        </nav>
    </header>
    <main>
        <div id="content">
            <h2>欢迎来到商店</h2>
            <p>请选择上方选项开始浏览。</p>
        </div>
    </main>
    <script src="js/app.js"></script>
</body>
</html>';
    }
}
