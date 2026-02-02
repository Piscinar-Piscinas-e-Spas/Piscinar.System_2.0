<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Códigos escaneados</title>
  <meta http-equiv="refresh" content="2"> <!-- atualiza a cada 2s -->
</head>
<body>
  <h2>Códigos recebidos:</h2>
  <pre>
<?php
if (file_exists("codes.txt")) {
    echo htmlspecialchars(file_get_contents("codes.txt"));
} else {
    echo "Nenhum código escaneado ainda...";
}
?>
  </pre>
</body>
</html>