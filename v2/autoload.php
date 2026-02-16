<?php

$server = $_SERVER['SERVER_NAME'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');


if ($server === 'localhost') {
    // require_once __DIR__ . '/config/secretConstants.dev.php';
    require_once __DIR__ . '/config/secretConstants.prod.php'; //provisorio
} else {
    require_once __DIR__ . '/config/secretConstants.prod.php';
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
