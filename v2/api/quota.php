<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$sessao_id = $_SERVER['HTTP_X_SESSION_ID'] ?? null;

if (!$sessao_id) {
    http_response_code(401);
    echo json_encode(['erro' => 'Sessão não identificada']);
    exit;
}

$limite = TOTAL_USER_PROMPTS_PER_TIME ?? 5;

// ============================
// CONTA PROMPTS NAS ÚLTIMAS 24H
// ============================
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) AS total,
        MIN(data_criacao) AS mais_antigo
    FROM chat_logs
    WHERE sessao_id = ?
      AND data_criacao >= (NOW() - INTERVAL 24 HOUR)
");
$stmt->execute([$sessao_id]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

$usados = (int) $dados['total'];
$disponiveis = max(0, $limite - $usados);

$resetEm = null;
if ($dados['mais_antigo']) {
    $resetEm = strtotime($dados['mais_antigo']) + (PROMP_EXPIRATION_TIME_HOURS * 3600) - time();
    if ($resetEm < 0) $resetEm = 0;
}

echo json_encode([
    'limite' => $limite,
    'usados' => $usados,
    'disponiveis' => $disponiveis,
    'bloqueado' => $disponiveis === 0,
    'reset_em_segundos' => $resetEm
]);
