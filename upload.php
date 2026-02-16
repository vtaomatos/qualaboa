<?php
// upload.php

$targetDir = __DIR__ . "/flyer/";

if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $arquivo = $_FILES['file'];
    $nome = basename($arquivo['name']);
    $destino = $targetDir . $nome;

    if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
        echo "✅ Upload de $nome concluído!";
    } else {
        echo "❌ Erro ao mover o arquivo.";
    }
} else {
    echo "⚠️ Nenhum arquivo recebido.";
}
