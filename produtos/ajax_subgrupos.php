<?php
include '../includes/db.php';

$grupo = $_GET['grupo'] ?? '';

$options = '';
if (!empty($grupo)) {
    $stmt = $pdo->prepare("SELECT DISTINCT subgrupo FROM produtos 
                          WHERE grupo = ? AND subgrupo IS NOT NULL");
    $stmt->execute([$grupo]);
    while ($row = $stmt->fetch()) {
        $options .= "<option value='{$row['subgrupo']}'>";
    }
}

echo $options;
?>