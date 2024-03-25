<?php

namespace App\Comms;

use Illuminate\Support\Facades\Http;

class Connection
{
    /**
     * @param  array <string, mixed>  $headers
     * @param  array <string, mixed>  $query
     * @return array<string, mixed>
     */
    public function sendGet(
        string $url,
        array $headers = [],
        array $query = [],
        int $connect_timeout = 0,
        int $response_timeout = 0
    ): array {
        $conn = Http::withHeaders($headers);
        if ($connect_timeout > 0) {
            $conn->connectTimeout($connect_timeout);
        }

        if ($response_timeout > 0) {
            $conn->timeout($response_timeout);
        }

        return $conn->get(url: $url, query: $query)->json();
    }

    /**
     * @param  array <string, mixed>  $headers
     * @param  array <string, mixed>  $data
     * @return array<string, mixed>
     */
    public function sendPost(
        string $url,
        array $headers = [],
        array $data = [],
        int $connect_timeout = 0,
        int $response_timeout = 0
    ): array {
        $conn = Http::withHeaders($headers);
        if ($connect_timeout > 0) {
            $conn->connectTimeout($connect_timeout);
        }

        if ($response_timeout > 0) {
            $conn->timeout($response_timeout);
        }

        return $conn->post(url: $url, data: $data)->json();
    }
}
