<?php

namespace App\Services;

require_once __DIR__ . '/../../autoload.php';

use App\Repositories\CategoriaRepository;

class CategoriaService
{
    private CategoriaRepository $repository;

    public function __construct(CategoriaRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listarCategoriasGrandes(): array
    {
        return $this->repository->getAll();
    }

    public function listarCategoriasGrandesPorCategoriaTecnicaId(int $categoriaTecnicaId): array
    {
        return $this->repository->getCategoriasGrandesByCategoriaTecnicaId($categoriaTecnicaId);
    }

    public function listarCategoriasTecnicasPorCategoriaGrandeId(int $categoriaGrandeId): array
    {
        return $this->repository->getCategoriasTecnicasByCategoriaGrandeId($categoriaGrandeId);
    }
}