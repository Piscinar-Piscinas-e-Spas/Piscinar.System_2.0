<?php

namespace App\Views;

class ApiResponse
{
    public static function send($statusCode, array $payload)
    {
        http_response_code((int) $statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
