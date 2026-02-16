<?php

namespace App;

require_once __DIR__ . '/../autoload.php';

use App\Database;
use App\Repositories\EventRepository;
use App\Services\EventService;
use App\Repositories\CategoriaRepository;
use App\Services\CategoriaService;

class Container {

    public static function eventService(): EventService {

        $conn = Database::connect();

        $repo = new EventRepository(conn: $conn);

        return new EventService($repo);
    }

    public static function categoriaService(): CategoriaService {

        $conn = Database::connect();

        $repo = new CategoriaRepository(conn: $conn);

        return new CategoriaService($repo);
    }
}
