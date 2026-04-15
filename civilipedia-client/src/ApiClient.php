<?php

class ApiClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim($_ENV['API_URL'] ?? 'http://localhost:3000/api', '/');
    }

    //  GET 
    public function get(string $endpoint, ?string $token = null): array
    {
        return $this->request('GET', $endpoint, null, $token);
    }

    //  POST 
    public function post(string $endpoint, array $data = [], ?string $token = null): array
    {
        return $this->request('POST', $endpoint, $data, $token);
    }

    //  PUT 
    public function put(string $endpoint, array $data = [], ?string $token = null): array
    {
        return $this->request('PUT', $endpoint, $data, $token);
    }

    //  PATCH 
    public function patch(string $endpoint, array $data = [], ?string $token = null): array
    {
        return $this->request('PATCH', $endpoint, $data, $token);
    }

    //  DELETE 
    public function delete(string $endpoint, ?string $token = null): array
    {
        return $this->request('DELETE', $endpoint, null, $token);
    }

    //  UPLOAD FILE (multipart/form-data) 
    public function uploadFile(
        string  $endpoint,
        string  $filePath,
        string  $mimeType,
        string  $fieldName = 'image',
        ?string $token = null
    ): array {
        $url = $this->baseUrl . $endpoint;

        $ch    = curl_init($url);
        $cfile = new CURLFile($filePath, $mimeType, basename($filePath));

        $headers = ['Accept: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [$fieldName => $cfile],
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($raw ?: '', true) ?? [];

        return ['status' => $status, 'body' => $body];
    }

    //  Méthode interne 
    private function request(
        string  $method,
        string  $endpoint,
        ?array  $data = null,
        ?string $token = null
    ): array {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init($url);

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
        ]);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($raw ?: '', true) ?? [];

        return ['status' => $status, 'body' => $body];
    }
}
