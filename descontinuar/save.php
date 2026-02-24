<?php
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['code'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'code ausente']);
    exit;
}

$code = trim((string)$_POST['code']);
$code = preg_replace('/\s+/', '', $code);
$code = preg_replace('/^\xEF\xBB\xBF/', '', $code);

if ($code === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'code vazio']);
    exit;
}

$file = __DIR__ . '/codes.txt';
if (!file_exists($file)) file_put_contents($file, '');

$fp = fopen($file, 'c+');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'não abriu o arquivo']);
    exit;
}

flock($fp, LOCK_EX);

rewind($fp);
$contents = stream_get_contents($fp);
$lines = preg_split("/\R/", (string)$contents);

$exists = false;
foreach ($lines as $line) {
    $v = trim($line);
    $v = preg_replace('/\s+/', '', $v);
    $v = preg_replace('/^\xEF\xBB\xBF/', '', $v);
    if ($v === $code) { $exists = true; break; }
}

if (!$exists) {
    fseek($fp, 0, SEEK_END);
    fwrite($fp, $code . PHP_EOL);
}

flock($fp, LOCK_UN);
fclose($fp);

echo json_encode(['ok' => true, 'saved' => !$exists, 'exists' => $exists], JSON_UNESCAPED_UNICODE);
