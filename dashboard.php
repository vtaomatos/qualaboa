<?php
/*************************************
 * CONFIGURAÇÕES BÁSICAS
 *************************************/

// --- LOGIN HARD CODED (TEMPORÁRIO) ---
$DASH_USER = 'admin';
$DASH_PASS = '123456';

// --- FUTURA CONEXÃO COM BANCO (BYPASS ATIVO) ---
/*
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=qualaboa",
        "usuario",
        "senha",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("Erro ao conectar no banco");
}
*/

// --- CAMINHO DOS LOGS ---
$LOG_DIR = __DIR__ . '/logs/';

// --- LOGIN SIMPLES ---
session_start();

if (isset($_POST['user'], $_POST['pass'])) {
    if ($_POST['user'] === $DASH_USER && $_POST['pass'] === $DASH_PASS) {
        $_SESSION['dash_auth'] = true;
    }
}

if (!($_SESSION['dash_auth'] ?? false)) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Login Dashboard</title>
        <style>
            body { font-family: Arial; background:#111; color:#fff; display:flex; height:100vh; align-items:center; justify-content:center; }
            form { background:#222; padding:30px; border-radius:8px; width:300px; }
            input, button { width:100%; padding:10px; margin-top:10px; }
            button { background:#0d6efd; border:0; color:#fff; cursor:pointer; }
        </style>
    </head>
    <body>
        <form method="POST">
            <h2>Dashboard</h2>
            <input name="user" placeholder="Usuário">
            <input name="pass" type="password" placeholder="Senha">
            <button>Entrar</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

/*************************************
 * FUNÇÕES DE LEITURA
 *************************************/

function lerLogs($pattern) {
    global $LOG_DIR;
    $dados = [];
    foreach (glob($LOG_DIR . $pattern) as $arquivo) {
        $linhas = file($arquivo, FILE_IGNORE_NEW_LINES);
        $dados = array_merge($dados, $linhas);
    }
    return $dados;
}

$logsAccess = lerLogs('access_*.log');
$logsSessao = lerLogs('sessao_*.log');

/*************************************
 * PROCESSAMENTO
 *************************************/

$totalAcessos = count($logsAccess);
$totalSessoes = count($logsSessao);

$rotas = [];
$cidades = [];
$bairros = [];
$tempoTotal = 0;

foreach ($logsSessao as $linha) {
    preg_match('/Tempo:(\d+)s/', $linha, $t);
    preg_match('/Cidade:([^|]+)/', $linha, $c);
    preg_match('/Bairro:([^|]+)/', $linha, $b);
    preg_match('/Rota:([^|]+)/', $linha, $r);

    $tempo = intval($t[1] ?? 0);
    $cidade = trim($c[1] ?? 'Desconhecida');
    $bairro = trim($b[1] ?? 'Desconhecido');
    $rota = trim($r[1] ?? '/');

    $tempoTotal += $tempo;

    $cidades[$cidade] = ($cidades[$cidade] ?? 0) + 1;
    $bairros[$bairro] = ($bairros[$bairro] ?? 0) + 1;
    $rotas[$rota] = ($rotas[$rota] ?? 0) + 1;
}

arsort($cidades);
arsort($bairros);
arsort($rotas);

$tempoMedio = $totalSessoes > 0 ? round($tempoTotal / $totalSessoes, 1) : 0;

/*************************************
 * HTML
 *************************************/
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Qual a Boa</title>
    <style>
        body { font-family: Inter, Arial; background:#f4f6f8; margin:0; padding:20px; }
        h1 { margin-bottom:10px; }
        .cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:15px; }
        .card { background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,.08); }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { padding:8px; border-bottom:1px solid #eee; text-align:left; }
        th { background:#fafafa; }
        .small { color:#666; font-size:13px; }
    </style>
</head>
<body>

<h1>📊 Dashboard — Qual a Boa</h1>
<p class="small">Baseado em logs locais (arquivo)</p>

<div class="cards">
    <div class="card"><h3>Total de Acessos</h3><strong><?= $totalAcessos ?></strong></div>
    <div class="card"><h3>Sessões Reais</h3><strong><?= $totalSessoes ?></strong></div>
    <div class="card"><h3>Tempo Médio</h3><strong><?= $tempoMedio ?>s</strong></div>
</div>

<h2>🏙️ Top Cidades</h2>
<table>
<?php foreach (array_slice($cidades, 0, 10) as $c => $v): ?>
<tr><td><?= htmlspecialchars($c) ?></td><td><?= $v ?></td></tr>
<?php endforeach; ?>
</table>

<h2>📍 Top Bairros</h2>
<table>
<?php foreach (array_slice($bairros, 0, 10) as $b => $v): ?>
<tr><td><?= htmlspecialchars($b) ?></td><td><?= $v ?></td></tr>
<?php endforeach; ?>
</table>

<h2>🛣️ Rotas Mais Acessadas</h2>
<table>
<?php foreach ($rotas as $r => $v): ?>
<tr><td><?= htmlspecialchars($r) ?></td><td><?= $v ?></td></tr>
<?php endforeach; ?>
</table>

</body>
</html>
