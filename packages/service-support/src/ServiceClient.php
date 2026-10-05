<?php
namespace Pharmacy\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServiceClient
{
    public function request(string $service, string $method, string $path, ?array $data = null): array
    {
        $url = config('services.'.$service);
        $key = config('services.key');
        if (!$url || !is_string($key) || strlen($key) < 32) {
            throw new HttpException(503, 'Service URL and SERVICE_KEY must be configured.');
        }
        try {
            $options = ['headers' => ['Accept' => 'application/json', 'X-Service-Key' => $key], 'http_errors' => false];
            if ($data !== null) { $options['json'] = $data; }
            $response = (new Client(['connect_timeout' => 3, 'timeout' => 10]))->request($method, rtrim($url, '/').$path, $options);
        } catch (TransferException $e) {
            throw new HttpException(503, 'Upstream service is unavailable.');
        }
        $body = json_decode((string) $response->getBody(), true);
        if ($response->getStatusCode() >= 400) {
            throw new HttpException($response->getStatusCode(), $body['message'] ?? 'Upstream request failed.');
        }
        if (!is_array($body) || !array_key_exists('data', $body)) {
            throw new HttpException(502, 'Invalid upstream response.');
        }
        return $body['data'] ?? [];
    }
}
