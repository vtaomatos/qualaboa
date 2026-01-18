<?php
$max_requests = 5;
$time_window = 60*60*24; // 1 dia

$user_ip = $_SERVER['REMOTE_ADDR'];

$rate_limit_dir = __DIR__ . '/storage/';
if (!is_dir($rate_limit_dir)) {
    mkdir($rate_limit_dir, 0777, true);
}

$ip_file = $rate_limit_dir . md5($user_ip) . '.json';

$requests = [];

if (file_exists($ip_file)) {
    $requests = json_decode(file_get_contents($ip_file), true) ?: [];
}

$now = time();

// remove requisições antigas
$requests = array_filter(
    $requests,
    fn($t) => $t > ($now - $time_window)
);

if (count($requests) >= $max_requests) {
    // SÓ MARCA O STATUS
    $_SERVER['RATE_LIMIT_EXCEEDED'] = true;
    return;
}

// registra requisição
$requests[] = $now;
file_put_contents($ip_file, json_encode($requests));
