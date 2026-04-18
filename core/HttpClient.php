<?php

namespace Core;

/**
 * 外部服务客户端基类
 * 用于与其他微服务通信
 */
class HttpClient
{
    private string $baseUrl;
    private array $defaultHeaders = [];
    private int $timeout = 10;

    public function __construct(string $baseUrl, array $headers = [])
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->defaultHeaders = array_merge([
            'Content-Type' => 'application/json',
            'User-Agent' => 'MVC-Framework/1.0',
        ], $headers);
    }

    /**
     * 发送 HTTP 请求
     */
    private function request(string $method, string $endpoint, ?array $data = null, array $headers = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $headers = array_merge($this->defaultHeaders, $headers);

        $options = [
            'http' => [
                'method' => $method,
                'header' => $this->formatHeaders($headers),
                'timeout' => $this->timeout,
            ]
        ];

        if ($data !== null && in_array($method, ['POST', 'PUT'])) {
            $options['http']['content'] = json_encode($data);
        }

        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new \Exception('请求失败: ' . $url);
            }

            return json_decode($response, true) ?? [];
        } catch (\Exception $e) {
            throw new \Exception('HTTP 请求错误: ' . $e->getMessage());
        }
    }

    /**
     * 格式化请求头
     */
    private function formatHeaders(array $headers): string
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = "{$key}: {$value}";
        }
        return implode("\r\n", $formatted);
    }

    /**
     * GET 请求
     */
    public function get(string $endpoint, array $headers = []): array
    {
        return $this->request('GET', $endpoint, null, $headers);
    }

    /**
     * POST 请求
     */
    public function post(string $endpoint, ?array $data = null, array $headers = []): array
    {
        return $this->request('POST', $endpoint, $data, $headers);
    }

    /**
     * PUT 请求
     */
    public function put(string $endpoint, ?array $data = null, array $headers = []): array
    {
        return $this->request('PUT', $endpoint, $data, $headers);
    }

    /**
     * DELETE 请求
     */
    public function delete(string $endpoint, array $headers = []): array
    {
        return $this->request('DELETE', $endpoint, null, $headers);
    }

    /**
     * 设置超时时间
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * 添加请求头
     */
    public function withHeader(string $key, string $value): self
    {
        $this->defaultHeaders[$key] = $value;
        return $this;
    }

    /**
     * 使用 Bearer Token
     */
    public function withBearerToken(string $token): self
    {
        return $this->withHeader('Authorization', "Bearer {$token}");
    }
}
