<?php

namespace App\Repositories;

require_once __DIR__ . '/../../autoload.php';

use App\Database;

class CategoriaRepository
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAll(): array
    {
        $stmt = $this->conn->prepare('
        SELECT 
            cg.id AS categoria_grande_id,
            cg.nome AS categoria_grande_nome,
            cg.cor_mapa,
            cg.created_at,
            ct.id AS categoria_tecnica_id,
            ct.nome AS categoria_tecnica_nome
        FROM 
            categorias_grandes cg
        INNER JOIN 
            categorias_tecnicas ct ON ct.categoria_grande_id = cg.id
        GROUP BY
            cg.id;
        ');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCategoriasGrandesByCategoriaTecnicaId(int $categoriaTecnicaId): array
    {
        $sql = "
            SELECT 
                cg.id, 
                cg.nome, 
                cg.cor_mapa
            FROM categorias_grandes cg
            JOIN categorias_tecnicas ct ON ct.categoria_grande_id = cg.id
            WHERE ct.id = ?
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$categoriaTecnicaId]);
        return $stmt->fetchAll();
    }

    public function getCategoriasTecnicasByCategoriaGrandeId(int $categoriaGrandeId): array
    {
        $sql = "
            SELECT 
                ct.id, 
                ct.nome
            FROM categorias_tecnicas ct
            WHERE ct.categoria_grande_id = ?
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$categoriaGrandeId]);
        return $stmt->fetchAll();
    }
}