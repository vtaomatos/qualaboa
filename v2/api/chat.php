<?php
// api/chat.php
require_once './../config/secretConstants.prod.php';
require_once './../config/db.php';

error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

$debug = isset($_GET['debug']);

function log_event($msg)
{
    global $debug;
    $prefix = date('[Y-m-d H:i:s] ');
    if ($debug)
        echo "<pre>$prefix$msg</pre>";
    error_log($prefix . $msg, 3, './chat-logs.txt');
}

/* ============================
   INPUT + SESSÃO
============================ */

$input = json_decode(file_get_contents('php://input'), true) ?? [];
log_event("Entrada recebida: " . json_encode($input));

$pergunta = trim($input['pergunta'] ?? '');
$data = trim($input['data'] ?? '');
$hora = trim($input['hora'] ?? '');
$eventoIds = array_map('intval', $input['eventos_id'] ?? []);

$sessaoId = $_SERVER['HTTP_X_SESSION_ID'] ?? null;

if (!$sessaoId) {
    http_response_code(401);
    echo json_encode(['erro' => 'Sessão não identificada']);
    exit;
}

if (!$pergunta) {
    echo json_encode(['erro' => 'Pergunta vazia']);
    exit;
}

/* ============================
   QUOTA (24h)
============================ */

$stmt = $conn->prepare("
    SELECT COUNT(*)
    FROM chat_logs
    WHERE sessao_id = ?
      AND data_criacao >= (NOW() - INTERVAL 24 HOUR)
");
$stmt->execute([$sessaoId]);
$usados = (int) $stmt->fetchColumn();

$limite = 5;

if ($usados >= $limite) {
    echo json_encode([
        'resposta' => [
            'ordem' => [],
            'explicacao' => '⚠️ Você já atingiu seu limite de 5 prompts nas últimas 24h.'
        ],
        'quota_excedida' => true
    ]);
    exit;
}

/* ============================
   BUSCA EVENTOS
============================ */

try {
    if (!$eventoIds)
        throw new Exception('Nenhum evento disponível');

    $dataFiltro = $data ?: date(format: 'Y-m-d');
    $horaFiltro = $hora ?: '00:00:00';

    $placeholders = implode(',', $eventoIds);

    $sql = "
        SELECT id, titulo, data_evento, descricao, endereco, categoria, tags
        FROM eventos
        WHERE id IN ($placeholders)
          AND DATE(data_evento) = :data
          AND TIME(data_evento) >= :hora
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':data' => $dataFiltro,
        ':hora' => $horaFiltro
    ]);

    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    log_event("Eventos carregados: " . count($eventos));

} catch (Exception $e) {
    log_event("Erro ao buscar eventos: " . $e->getMessage());
    echo json_encode(['erro' => 'Erro ao buscar eventos.']);
    exit;
}

/* ============================
   MONTA TEXTO PARA IA
============================ */

$listaEventosTexto = implode("\n", array_map(function ($e) {
    return "ID: {$e['id']}
Título: {$e['titulo']}
Data: {$e['data_evento']}
Descrição: {$e['descricao']}
Endereço: {$e['endereco']}
Categoria: {$e['categoria']}
Search Index: {$e['tags']}
";
}, $eventos));

log_event("Eventos enviados para IA:\n" . $listaEventosTexto);

/* ============================
   PROMPT (BLINDADO)
============================ */

$mensagemSistema = <<<TXT
Você é um assistente especializado em recomendar eventos.

Com base na lista de eventos abaixo e no pedido do usuário, responda EXATAMENTE neste formato:

###ORDEM###
id1,id2,id3

###EXPLICACAO###
HTML amigável explicando os eventos, com data e local. HTML válido seguindo OBRIGATORIAMENTE esta estrutura:

<p>Introdução amigável explicando o contexto.</p>

<ul>
<li>
<strong>{Titulo do evento}</strong><br>
📅 {Data no formato DD/MM/YYYY HH:MM}<br>
📍 {Endereço}<br>
<span>{Descrição curta e amigável}</span>
</li>
</ul>

Regras obrigatórias:
- Use sempre <ul> e <li> para listar eventos
- Nunca use numeração tipo "1." ou "2."
- Nunca use markdown (**texto**)
- Use emojis 📅 e 📍
- Máximo 2 frases por evento
- HTML simples e limpo

Lista de eventos:
$listaEventosTexto
TXT;

/* ============================
   OPENAI
============================ */

$apiKey = APIKEY ?? '';
if (!$apiKey) {
    echo json_encode(['erro' => 'API Key não configurada']);
    exit;
}

$payload = [
    'model' => 'gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => $mensagemSistema],
        ['role' => 'user', 'content' => $pergunta]
    ],
    'temperature' => 0.8,
    'max_tokens' => 500
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    log_event("Erro CURL: " . curl_error($ch));
    echo json_encode(['erro' => 'Erro ao consultar IA']);
    exit;
}
curl_close($ch);

$responseData = json_decode($response, true);
$content = $responseData['choices'][0]['message']['content'] ?? '';

log_event("Resposta bruta IA:\n" . $content);

/* ============================
   EXTRAÇÃO BLINDADA
============================ */

preg_match('/###ORDEM###\s*(.*?)\s*###EXPLICACAO###/s', $content, $m1);
preg_match('/###EXPLICACAO###\s*(.*)$/s', $content, $m2);

$ordem = [];
if (!empty($m1[1])) {
    $ordem = array_values(array_filter(array_map(
        'intval',
        explode(',', trim($m1[1]))
    )));
}

$explicacao = trim($m2[1] ?? '');

if (!$explicacao) {
    $explicacao = 'Nenhum evento relevante encontrado.';
}

/* ============================
   JSON FINAL (SEMPRE VÁLIDO)
============================ */

$respostaFinal = [
    'ordem' => $ordem,
    'explicacao' => $explicacao
];

/* ============================
   SALVA LOG (QUOTA)
============================ */

try {
    require './../config/db.php'; 
    
    $stmt = $conn->prepare("
        INSERT INTO chat_logs (sessao_id, pergunta, resposta, data_criacao)
        VALUES (:sessao_id, :pergunta, :resposta, NOW())
    ");
    $stmt->execute([
        ':sessao_id' => $sessaoId,
        ':pergunta' => $pergunta,
        ':resposta' => json_encode($respostaFinal, JSON_UNESCAPED_UNICODE)
    ]);
} catch (Exception $e) {
    log_event("Erro ao salvar chat_logs: " . $e->getMessage());
}

/* ============================
   RESPOSTA FINAL
============================ */

echo json_encode([
    'resposta' => $respostaFinal
], JSON_UNESCAPED_UNICODE);
