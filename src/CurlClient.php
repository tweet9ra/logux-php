<?php


namespace tweet9ra\Logux;


class CurlClient
{
    public function request(string $url, array $commands)
    {
        $body = json_encode($commands);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_RETURNTRANSFER => 0,
            CURLOPT_TIMEOUT_MS => 5,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $body
        ]);

        curl_exec($ch);
    }
}