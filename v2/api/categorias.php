<?php

require_once __DIR__ . '/../autoload.php';

use App\Container;

header('Content-Type: application/json');

try {

    $categoriaTecnicaId = $_GET['categoria_tecnica_id'] ?? null;
    $categoriaGrandeId = $_GET['categoria_grande_id'] ?? null;

    $service = Container::categoriaService();

    if ($categoriaTecnicaId) {
        $categoriasGrandes = $service->listarCategoriasGrandesPorCategoriaTecnicaId($categoriaTecnicaId);
        echo json_encode($categoriasGrandes);
    } elseif ($categoriaGrandeId) {
        $categoriasTecnicas = $service->listarCategoriasTecnicasPorCategoriaGrandeId($categoriaGrandeId);
        echo json_encode($categoriasTecnicas);
    } else {
        $categoriasGrandes = $service->listarCategoriasGrandes();
        echo json_encode($categoriasGrandes);
    }

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno']);
}