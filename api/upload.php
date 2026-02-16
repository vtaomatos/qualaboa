<?php

$TOKEN_SECRETO = "SEU_TOKEN_AQUI";
$BASE_UPLOAD_DIR = __DIR__ . "/../uploads/stories_capturados/";
$BASE_UPLOAD_URL = "/uploads/stories/";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Método não permitido");
}

$headers = getallheaders();
if (($headers['X-API-KEY'] ?? null) !== $TOKEN_SECRETO) {
    http_response_code(403);
    exit("Token inválido");
}

if (!isset($_FILES['imagem'])) {
    http_response_code(400);
    exit("Arquivo não enviado");
}

$execId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['exec_id'] ?? '');
$conta  = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['conta'] ?? '');
$index  = intval($_POST['story_index'] ?? 0);

if (!$execId || !$conta) {
    http_response_code(400);
    exit("Parâmetros inválidos");
}

$arquivo = $_FILES['imagem'];

if ($arquivo['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit("Erro no upload");
}

// Validação MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $arquivo['tmp_name']);
finfo_close($finfo);

$permitidos = [
    'image/png' => 'png',
    'image/jpeg' => 'jpg',
    'image/webp' => 'webp'
];

if (!isset($permitidos[$mime])) {
    http_response_code(400);
    exit("Tipo inválido");
}

$extensao = $permitidos[$mime];

// Diretório final
$diretorioFinal = $BASE_UPLOAD_DIR . "$execId/$conta/";

if (!is_dir($diretorioFinal)) {
    mkdir($diretorioFinal, 0755, true);
}

// Nome igual ao padrão do robô
$nomeFinal = "story_" . $index . "." . $extensao;

$caminhoFinal = $diretorioFinal . $nomeFinal;

if (!move_uploaded_file($arquivo['tmp_name'], $caminhoFinal)) {
    http_response_code(500);
    exit("Erro ao salvar");
}

$urlPublica = $BASE_UPLOAD_URL . "$execId/$conta/" . $nomeFinal;

header("Content-Type: application/json");
echo json_encode([
    "sucesso" => true,
    "caminho" => $urlPublica
]);
