<?php
/*************************************
 * CONFIGURAÇÕES
 *************************************/
date_default_timezone_set('America/Sao_Paulo');


require_once __DIR__ . '/config/db.php'; // cria $conn

$LOG_DIR = __DIR__ . '/logs/';


$DASH_USER = DASH_USER ?? 'admin';
$DASH_PASS = DASH_PASS ?? '12345677';

/*************************************
 * LOGIN / LOGOUT
 *************************************/
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: dashboard.php');
    exit;
}

if (isset($_POST['user'], $_POST['pass'])) {
    if ($_POST['user'] === $DASH_USER && $_POST['pass'] === $DASH_PASS) {
        $_SESSION['dash_auth'] = true;
    }
}

if (!($_SESSION['dash_auth'] ?? false)) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Login</title>
    </head>
    <body style="background:#111;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh">
        <form method="POST" style="background:#222;padding:30px">
            <h3>Dashboard</h3>
            <input name="user" placeholder="Usuário"><br><br>
            <input name="pass" type="password" placeholder="Senha"><br><br>
            <button>Entrar</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}

$host = $_SERVER['HTTP_HOST'] ?? 'unknown';

/*************************************
 * LOGS DE ACESSO (ARQUIVO)
 *************************************/
$logsAccess = [];
foreach (glob($LOG_DIR . 'access_*.log') as $file) {
    $logsAccess = array_merge($logsAccess, file($file, FILE_IGNORE_NEW_LINES));
}

$totalAcessos = count($logsAccess);

/**
 * ROTAS — SOMENTE PELOS LOGS DE ACESSO
 */
$rotas = [];

foreach ($logsAccess as $linha) {
    if (preg_match('/Rota:([^|]+)/i', $linha, $m)) {
        $rota = trim($m[1]);
        $rotas[$rota] = ($rotas[$rota] ?? 0) + 1;
    }
}

arsort($rotas);

/*************************************
 * LOGS DE SESSÃO (BANCO)
 *************************************/
$stmt = $conn->query("
    SELECT
        sessao_id,
        duracao_segundos AS tempo_total,
        lat,
        lng
    FROM sessoes
    WHERE host = " . $conn->quote($host)
);

$sessoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalSessoes = count($sessoes);

/*************************************
 * PROCESSAMENTO DE SESSÕES
 *************************************/
$tempoTotal = 0;
$heatmap = [];

foreach ($sessoes as $s) {
    $tempoTotal += (int)$s['tempo_total'];

    if (!empty($s['lat']) && !empty($s['lng'])) {
        $heatmap[] = [
            'lat' => (float)$s['lat'],
            'lng' => (float)$s['lng']
        ];
    }
}

function formatarTempo($segundos) {
    if ($segundos < 60) {
        return "00:" . str_pad($segundos, 2, '0', STR_PAD_LEFT);
    }

    if ($segundos < 3600) {
        $m = floor($segundos / 60);
        $s = $segundos % 60;
        return str_pad($m, 2, '0', STR_PAD_LEFT) . ":" . str_pad($s, 2, '0', STR_PAD_LEFT);
    }

    if ($segundos < 86400) {
        $h = floor($segundos / 3600);
        $m = floor(($segundos % 3600) / 60);
        $s = $segundos % 60;
        return str_pad($h, 2, '0', STR_PAD_LEFT) . ":" .
               str_pad($m, 2, '0', STR_PAD_LEFT) . ":" .
               str_pad($s, 2, '0', STR_PAD_LEFT);
    }

    $d = floor($segundos / 86400);
    $resto = $segundos % 86400;
    $h = floor($resto / 3600);
    $m = floor(($resto % 3600) / 60);
    $s = $resto % 60;

    return "{$d}d " .
           str_pad($h, 2, '0', STR_PAD_LEFT) . ":" .
           str_pad($m, 2, '0', STR_PAD_LEFT) . ":" .
           str_pad($s, 2, '0', STR_PAD_LEFT);
}

$tempoMedio = $totalSessoes ? round($tempoTotal / $totalSessoes, 1) : 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — Qual a Boa</title>
    <style>
        body { font-family: Arial; background:#f4f6f8; padding:20px; }
        .cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:15px; }
        .card { background:#fff; padding:20px; border-radius:8px; }
        table { width:100%; border-collapse:collapse; }
        td { padding:6px; border-bottom:1px solid #eee; }
        #heatmap { height:500px; margin-top:20px; }
        .topbar { display:flex; justify-content:space-between; align-items:center; }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&libraries=visualization"></script>
</head>
<body>

<div class="topbar">
    <h1>📊 Dashboard — Qual a Boa</h1>
    <a href="?logout=1" style="text-decoration:none">🚪 Logout</a>
</div>

<div class="cards">
    <div class="card"><strong>Acessos</strong><br><?= $totalAcessos ?></div>
    <div class="card"><strong>Sessões</strong><br><?= $totalSessoes ?></div>
    <div class="card"><strong>Tempo Médio</strong><br><?= formatarTempo($tempoMedio) ?></div>
</div>

<h2>🛣️ Rotas (logs de acesso)</h2>
<table>
<?php foreach ($rotas as $r => $v): ?>
    <tr>
        <td><?= htmlspecialchars($r) ?></td>
        <td><?= $v ?></td>
    </tr>
<?php endforeach; ?>
</table>

<h2>🔥 Heatmap</h2>
<div id="heatmap"></div>

<script>
const heatmapData = <?= json_encode($heatmap) ?>.map(p =>
    new google.maps.LatLng(p.lat, p.lng)
);

function initHeatmap() {
    const map = new google.maps.Map(document.getElementById('heatmap'), {
        zoom: 12,
        center: { lat: -23.9608, lng: -46.3331 }
    });

    new google.maps.visualization.HeatmapLayer({
        data: heatmapData,
        radius: 25
    }).setMap(map);
}
window.onload = initHeatmap;
</script>

</body>
</html>
