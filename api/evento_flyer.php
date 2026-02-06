<?php
require_once __DIR__ . '/../config/db.php';

$id = intval($_GET['id'] ?? 0);

if (!$id) {
  echo json_encode([]);
  exit;
}

$sql = "
  SELECT 
    imagem_base64,
    flyer_imagem,
    flyer_html
  FROM eventos
  WHERE id = :id
  LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
  echo json_encode([]);
  exit;
}

// prioridade de exibição
if (!empty($evento['imagem_base64'])) {
  echo json_encode([
    'imagem' => 'data:image/png;base64,' . $evento['imagem_base64']
  ]);
  exit;
}

if (!empty($evento['flyer_imagem'])) {
  echo json_encode([
    'imagem' => $evento['flyer_imagem']
  ]);
  exit;
}

// fallback HTML (opcional)
if (!empty($evento['flyer_html'])) {
  echo json_encode([
    'html' => $evento['flyer_html']
  ]);
  exit;
}

echo json_encode([]);
