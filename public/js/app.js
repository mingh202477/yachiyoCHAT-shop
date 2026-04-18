const API_BASE = ''; // API路径

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('goods-btn').addEventListener('click', loadGoods);
    document.getElementById('backpack-btn').addEventListener('click', loadBackpack);
    document.getElementById('history-btn').addEventListener('click', loadHistory);
});

async function loadGoods() {
    try {
        const response = await fetch(`${API_BASE}/goods`);
        const data = await response.json();
        if (data.success) {
            displayGoods(data.data.goods);
        } else {
            alert('加载商品失败: ' + data.message);
        }
    } catch (error) {
        alert('网络错误: ' + error.message);
    }
}

function displayGoods(goods) {
    const content = document.getElementById('content');
    content.innerHTML = '<h2>商品列表</h2>';
    
    // 搜索表单
    content.innerHTML += `
        <form id="search-form">
            <input type="text" id="search-keyword" placeholder="搜索商品...">
            <button type="submit">搜索</button>
        </form>
    `;
    
    if (goods.length === 0) {
        content.innerHTML += '<p>未找到商品</p>';
        return;
    }
    
    // 商品列表
    goods.forEach(good => {
        content.innerHTML += `
            <div class="product">
                <h3>${good.name}</h3>
                <p>价格: ${good.price} 金币</p>
                <p>库存: ${good.stock}</p>
                <p>描述: ${good.description}</p>
                <button onclick="purchaseGood(${good.id})">购买</button>
            </div>
        `;
    });
    
    // 绑定搜索事件
    document.getElementById('search-form').addEventListener('submit', (e) => {
        e.preventDefault();
        searchGoods();
    });
}

async function searchGoods() {
    const keyword = document.getElementById('search-keyword').value;
    try {
        const response = await fetch(`${API_BASE}/goods/search?keyword=${encodeURIComponent(keyword)}`);
        const data = await response.json();
        if (data.success) {
            displayGoods(data.data);
        } else {
            alert('搜索失败: ' + data.message);
        }
    } catch (error) {
        alert('网络错误: ' + error.message);
    }
}

async function purchaseGood(goodId) {
    const userId = prompt('请输入用户ID:'); // 简单起见，使用prompt
    if (!userId) return;
    
    try {
        const response = await fetch(`${API_BASE}/purchase`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: parseInt(userId),
                good_id: goodId,
                quantity: 1
            })
        });
        const data = await response.json();
        if (data.success) {
            alert('购买成功!');
            loadGoods(); // 刷新商品列表
        } else {
            alert('购买失败: ' + data.message);
        }
    } catch (error) {
        alert('网络错误: ' + error.message);
    }
}

async function loadBackpack() {
    const userId = prompt('请输入用户ID:');
    if (!userId) return;
    
    try {
        const response = await fetch(`${API_BASE}/backpack/${userId}`);
        const data = await response.json();
        if (data.success) {
            displayBackpack(data.data);
        } else {
            alert('加载背包失败: ' + data.message);
        }
    } catch (error) {
        alert('网络错误: ' + error.message);
    }
}

function displayBackpack(items) {
    const content = document.getElementById('content');
    content.innerHTML = '<h2>我的背包</h2>';
    
    if (items.length === 0) {
        content.innerHTML += '<p>背包为空</p>';
        return;
    }
    
    content.innerHTML += '<table><thead><tr><th>商品名</th><th>数量</th></tr></thead><tbody>';
    items.forEach(item => {
        content.innerHTML += `<tr><td>${item.good_name}</td><td>${item.quantity}</td></tr>`;
    });
    content.innerHTML += '</tbody></table>';
}

async function loadHistory() {
    const userId = prompt('请输入用户ID:');
    if (!userId) return;
    
    try {
        const response = await fetch(`${API_BASE}/history/${userId}`);
        const data = await response.json();
        if (data.success) {
            displayHistory(data.data);
        } else {
            alert('加载历史失败: ' + data.message);
        }
    } catch (error) {
        alert('网络错误: ' + error.message);
    }
}

function displayHistory(transactions) {
    const content = document.getElementById('content');
    content.innerHTML = '<h2>购买历史</h2>';
    
    if (transactions.length === 0) {
        content.innerHTML += '<p>无购买记录</p>';
        return;
    }
    
    content.innerHTML += '<table><thead><tr><th>商品名</th><th>数量</th><th>总价</th><th>时间</th></tr></thead><tbody>';
    transactions.forEach(tx => {
        content.innerHTML += `<tr><td>${tx.good_name}</td><td>${tx.quantity}</td><td>${tx.total_price}</td><td>${tx.created_at}</td></tr>`;
    });
    content.innerHTML += '</tbody></table>';
}