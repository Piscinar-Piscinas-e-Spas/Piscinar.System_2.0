<?php
include '../includes/db.php';

$grupo = (string) ($_GET['grupo'] ?? '');
$controller = new \App\Controllers\ProdutoController($pdo);
$subgrupos = $controller->subgroups($grupo);

foreach ($subgrupos as $subgrupo) {
    echo "<option value='" . htmlspecialchars((string) $subgrupo, ENT_QUOTES, 'UTF-8') . "'>";
}
?>
