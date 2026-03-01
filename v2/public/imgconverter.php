<?php

require_once __DIR__ . "/../config/db.php";

$maxLoops = 1000;
$loop = 0;

while ($loop < $maxLoops) {
    $loop++;

    $eventos = $conn->query("
        SELECT id, flyer_imagem, imagem_base64 
        FROM eventos 
        WHERE imagem_base64 IS NOT NULL
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (count($eventos) === 0) {
        break;
    }

    foreach ($eventos as $evento) {

        $id = $evento['id'];
        $base64 = $evento['imagem_base64'];

        if (!$base64) {
            continue;
        }

        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $base64);
        $data = base64_decode($base64);

        if (!$data) {
            echo "Erro ao decodificar base64 do evento $id\n";
            continue;
        }

        // Remove ./ inicial se existir
        $relativePath = ltrim($evento['flyer_imagem'], './');
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $relativePath;

        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Se já existir arquivo, não recria
        if (!file_exists($fullPath)) {

            $salvou = file_put_contents($fullPath, $data);

            if (!$salvou) {
                echo "Erro ao salvar imagem do evento $id\n";
                continue;
            }
        }

        // Agora sim, limpa o base64
        $stmt = $conn->prepare("
            UPDATE eventos 
            SET imagem_base64 = NULL 
            WHERE id = :id
        ");

        $stmt->execute(['id' => $id]);

        echo "Migrado evento $id\n";
    }
}

echo "Migração finalizada.\n";