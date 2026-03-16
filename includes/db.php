<?php
require_once dirname(__DIR__) . '/src/bootstrap.php';

$host = 'localhost';
$db   = 'piscinar_db';
$user = 'root';
$pass = ''; // XAMPP geralmente tem senha vazia

try {
    $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('<!-- Erro de conexão: ' . $e->getMessage() . ' -->');
}
?>
