<?php
header('Content-Type: application/json; charset=utf-8');

$file = __DIR__ . '/codes.txt';
$codes = [];

if (file_exists($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            // normaliza: remove espaços e caracteres invisíveis comuns
            $v = trim($line);
            $v = preg_replace('/\s+/', '', $v);
            $v = preg_replace('/^\xEF\xBB\xBF/', '', $v); // remove BOM se existir
            if ($v !== '') $codes[] = $v;
        }
    }
}

echo json_encode(['ok' => true, 'codes' => $codes], JSON_UNESCAPED_UNICODE);
