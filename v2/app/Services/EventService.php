<?php

namespace App\Services;

use App\Repositories\EventRepository;

class EventService
{
    private EventRepository $repository;

    public function __construct(EventRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listarEventosPorDia(
        string $data,
        string $hora,
    ): array {
        return $this->repository
            ->getByDay($data, $hora);
    }

    public function listarEventosPorSemana(
        string $data,
    ): array {
        return $this->repository
            ->getByDiaSemana(
                $data,
            );
    }
}
