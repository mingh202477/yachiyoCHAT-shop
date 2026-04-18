<?php
// 测试API
$url = 'http://localhost:8000/goods';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents($url, false, $context);
if ($response === false) {
    echo "API请求失败\n";
} else {
    $data = json_decode($response, true);
    echo "API响应:\n";
    print_r($data);
}
?>