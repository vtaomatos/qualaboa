<?php

require_once __DIR__ . '/../autoload.php';

use App\Container;

header('Content-Type: application/json');

try {

    $data = $_GET['data'] ?? date('Y-m-d');
    $hora = $_GET['hora'] ?? '00:00';
    $categoriasGrandes = $_GET['categorias'] ?? [];

    if (!is_array($categoriasGrandes)) {
        $categoriasGrandes = [$categoriasGrandes];
    }

    $service = Container::eventService();

    $eventos = $service->listarEventosPorSemana(
        $data
    );

    echo json_encode($eventos);

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno']);
}
