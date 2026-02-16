<?php

namespace App\Repositories;

use PDO;
use DateTime;

class EventRepository
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    public function getByDay(
        string $data,
        string $hora,
    ): array {

        $sql = "
            SELECT 
                e.id,
                e.titulo,
                e.data_evento,
                e.descricao,
                e.flyer_html,
                e.endereco,
                e.latitude,
                e.longitude,
                e.instagram,
                e.linkInstagram,
                ct.nome AS categoria_tecnica,
                cg.nome AS categoria_grande,
                cg.cor_mapa
            FROM eventos e
            JOIN categorias_tecnicas ct 
                ON ct.id = e.categoria_tecnica_id
            JOIN categorias_grandes cg 
                ON cg.id = ct.categoria_grande_id
            WHERE e.data_evento >= ?
            AND e.data_evento < DATE_ADD(?, INTERVAL 1 DAY)
            AND TIME(e.data_evento) >= ?
        ";

        $params = array_merge(
            [$data, $data, $hora],
        );

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByDiaSemana($data)
    {
        $inicio = new DateTime($data);
        $inicio->setTime(0, 0, 0);

        $fim = clone $inicio;
        $fim->modify('+7 days');

        $sql = "
        SELECT 
            e.id,
            e.titulo,
            e.data_evento,
            e.descricao,
            e.flyer_html,
            e.endereco,
            e.latitude,
            e.longitude,
            e.instagram,
            e.linkInstagram,
            ct.nome AS categoria_tecnica,
            cg.nome AS categoria_grande,
            cg.cor_mapa
        FROM eventos e
        JOIN categorias_tecnicas ct 
            ON ct.id = e.categoria_tecnica_id
        JOIN categorias_grandes cg 
            ON cg.id = ct.categoria_grande_id
        WHERE e.data_evento >= ?
        AND e.data_evento < ?
    ";

        $params = array_merge(
            [
                $inicio->format('Y-m-d H:i:s'),
                $fim->format('Y-m-d H:i:s')
            ]
        );

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



}
