<?php

namespace App\Views;

class AlertRenderer
{
    public static function make($type, $message)
    {
        return [
            'type' => $type,
            'message' => $message,
        ];
    }

    public static function fromStatus($status, array $map)
    {
        if ($status === '' || !isset($map[$status])) {
            return null;
        }

        return self::make($map[$status]['class'], $map[$status]['texto']);
    }

    public static function render($alert)
    {
        if (!is_array($alert) || empty($alert['message'])) {
            return '';
        }

        $type = htmlspecialchars((string) ($alert['type'] ?? 'info'), ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars((string) $alert['message'], ENT_QUOTES, 'UTF-8');

        return '<div class="alert alert-' . $type . '">' . $message . '</div>';
    }
}
