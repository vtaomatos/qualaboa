<?php
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['sessao_id'])) {
    http_response_code(400);
    exit;
}

$sessaoId = substr($data['sessao_id'], 0, 64);
$evento   = $data['evento'] ?? 'heartbeat';
$tempo    = intval($data['tempo'] ?? 0);
$rota     = substr($data['rota'] ?? '/', 0, 255);

$lat = isset($data['lat']) ? floatval($data['lat']) : null;
$lng = isset($data['lng']) ? floatval($data['lng']) : null;

// fallback Santos
$fallbackLat = -23.9608;
$fallbackLng = -46.3331;

// ignora fallback
if ($lat === $fallbackLat && $lng === $fallbackLng) {
    $lat = null;
    $lng = null;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
$agora = date('Y-m-d H:i:s');

/**
 * Verifica se sessão já existe
 */
$stmt = $conn->prepare("
    SELECT id, lat, lng
    FROM sessoes
    WHERE sessao_id = ?
");
$stmt->execute([$sessaoId]);
$sessao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sessao) {
    $host = $_SERVER['HTTP_HOST'] ?? 'unknown';


    // ==========================
    // INSERT NOVA SESSÃO
    // ==========================
    $stmt = $conn->prepare("
        INSERT INTO sessoes (
            sessao_id,
            ip,
            user_agent,
            inicio,
            ultimo_evento,
            duracao_segundos,
            heartbeats,
            lat,
            lng,
            rota_inicial,
            ultima_rota,
            encerrada,
            host
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)
    ");

    $stmt->execute([
        $sessaoId,
        $ip,
        $ua,
        $agora,
        $agora,
        $tempo,
        1,
        $lat,
        $lng,
        $rota,
        $rota,
        $host
    ]);

} else {

    // ==========================
    // UPDATE SESSÃO EXISTENTE
    // ==========================
    $sql = "
        UPDATE sessoes SET
            ultimo_evento = ?,
            duracao_segundos = duracao_segundos + ?,
            heartbeats = heartbeats + 1,
            ultima_rota = ?
    ";

    $params = [
        $agora,
        $tempo,
        $rota
    ];

    // só atualiza coordenadas se não forem null
    if ($lat !== null && $lng !== null) {
        $sql .= ", lat = ?, lng = ?";
        $params[] = $lat;
        $params[] = $lng;
    }

    if ($evento === 'end') {
        $sql .= ", encerrada = 1";
    }

    $sql .= " WHERE sessao_id = ?";

    $params[] = $sessaoId;

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
}

http_response_code(204);
