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
 * RANGE DE DATA
 *************************************/
$dataInicio = $_GET['inicio'] ?? date('Y-m-01');
$dataFim = $_GET['fim'] ?? date('Y-m-d');

/*************************************
 * LOGS DE ACESSO (ARQUIVO)
 *************************************/
$logsAccess = [];

foreach (glob($LOG_DIR . 'access_*.log') as $file) {
    foreach (file($file, FILE_IGNORE_NEW_LINES) as $linha) {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $linha, $m)) {
            if ($m[1] >= $dataInicio && $m[1] <= $dataFim) {
                $logsAccess[] = $linha;
            }
        }
    }
}

$totalAcessos = count($logsAccess);

/*************************************
 * MÉTRICAS DE ACESSO
 *************************************/
$porDia = [];
$porSemana = [];
$porMes = [];
$porDiaSemana = [];

foreach ($logsAccess as $linha) {
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})/', $linha, $m))
        continue;

    $dt = new DateTime($m[1]);

    $dia = $dt->format('Y-m-d');
    $semana = $dt->format('o-W');
    $mes = $dt->format('Y-m');
    $diaSemana = $dt->format('l');

    $porDia[$dia] = ($porDia[$dia] ?? 0) + 1;
    $porSemana[$semana] = ($porSemana[$semana] ?? 0) + 1;
    $porMes[$mes] = ($porMes[$mes] ?? 0) + 1;
    $porDiaSemana[$diaSemana] = ($porDiaSemana[$diaSemana] ?? 0) + 1;
}

arsort($porDia);
arsort($porSemana);
arsort($porMes);
arsort($porDiaSemana);

/*************************************
 * ROTAS
 *************************************/
$rotas = [];
foreach ($logsAccess as $linha) {
    if (preg_match('/Rota:([^|]+)/i', $linha, $m)) {
        $rota = trim($m[1]);
        $rotas[$rota] = ($rotas[$rota] ?? 0) + 1;
    }
}
arsort($rotas);

/*************************************
 * SESSÕES (BANCO)
 *************************************/
$stmt = $conn->prepare("
    SELECT
        sessao_id,
        duracao_segundos,
        lat,
        lng,
        inicio
    FROM sessoes
    WHERE host = ?
      AND DATE(inicio) BETWEEN ? AND ?
    ORDER BY inicio DESC
");
$stmt->execute([$host, $dataInicio, $dataFim]);
$sessoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalSessoes = count($sessoes);

/*************************************
 * BUSCAS POR SESSÃO (SE EXISTIR)
 *************************************/
$buscasPorSessao = [];

try {
    $stmt = $conn->query("
        SELECT sessao_id, termo
        FROM buscas
        WHERE DATE(inicio) BETWEEN '{$dataInicio}' AND '{$dataFim}'
    ");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $b) {
        $buscasPorSessao[$b['sessao_id']][] = $b['termo'];
    }
} catch (Exception $e) {
    // tabela não existe → segue a vida
}

/*************************************
 * PROCESSAMENTO
 *************************************/
$tempoTotal = 0;
$heatmap = [];

foreach ($sessoes as $s) {
    $tempoTotal += (int) $s['duracao_segundos'];

    if (!empty($s['lat']) && !empty($s['lng'])) {
        $heatmap[] = [
            'lat' => (float) $s['lat'],
            'lng' => (float) $s['lng']
        ];
    }
}

function formatarTempo($segundos)
{
    $h = floor($segundos / 3600);
    $m = floor(($segundos % 3600) / 60);
    $s = $segundos % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}

$tempoMedio = $totalSessoes ? round($tempoTotal / $totalSessoes) : 0;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Dashboard — Qual a Boa</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <style>
        /* Reset básico */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f4f6f8;
            padding: 20px;
            color: #333;
        }

        /* Topbar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .topbar h1 {
            font-size: 1.6em;
        }

        .topbar a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
            background: #eee;
            padding: 6px 12px;
            border-radius: 6px;
            transition: 0.2s;
        }

        .topbar a:hover {
            background: #ddd;
        }

        /* Filtros */
        form {
            margin-bottom: 20px;
        }

        form input,
        form button {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            margin-right: 8px;
        }

        form button {
            background: #36A2EB;
            color: #fff;
            border: none;
            cursor: pointer;
        }

        form button:hover {
            background: #2a85c4;
        }

        /* Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            text-align: center;
            font-weight: bold;
            font-size: 1.1em;
        }

        /* Tabelas */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            background: #f0f0f0;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .badge {
            display: inline-block;
            background: #eee;
            color: #333;
            padding: 3px 6px;
            border-radius: 4px;
            margin-right: 4px;
            font-size: 0.85em;
        }

        /* Heatmap */
        #heatmap {
            height: 500px;
            margin-top: 20px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        /* Gráficos */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .chart-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        /* Headings */
        h2 {
            margin-top: 40px;
            margin-bottom: 10px;
            font-size: 1.3em;
            color: #333;
        }

        ul {
            margin-left: 20px;
        }

        li {
            margin-bottom: 6px;
        }
    </style>


    <script
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&libraries=visualization"></script>
</head>

<body>

    <div class="topbar">
        <h1>📊 Dashboard — Qual a Boa</h1>
        <a href="?logout=1">🚪 Logout</a>
    </div>

    <form style="margin-top:15px">
        <input type="date" name="inicio" value="<?= $dataInicio ?>">
        <input type="date" name="fim" value="<?= $dataFim ?>">
        <button>Filtrar</button>
    </form>

    <div class="cards">
        <div class="card"><strong>Acessos</strong><br><?= $totalAcessos ?></div>
        <div class="card"><strong>Sessões</strong><br><?= $totalSessoes ?></div>
        <div class="card"><strong>Tempo Médio</strong><br><?= formatarTempo($tempoMedio) ?></div>
    </div>

    <h2>📈 Picos</h2>
    <ul>
        <li>Pico diário: <?= array_key_first($porDia) ?></li>
        <li>Pico semanal: <?= array_key_first($porSemana) ?></li>
        <li>Pico mensal: <?= array_key_first($porMes) ?></li>
        <li>Dia mais acessado: <?= array_key_first($porDiaSemana) ?></li>
    </ul>

    <h2>🛣️ Rotas</h2>
    <table>
        <?php foreach ($rotas as $r => $v): ?>
            <tr>
                <td><?= htmlspecialchars($r) ?></td>
                <td><?= $v ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>🧑‍💻 Sessões</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Quando</th>
            <th>Duração</th>
            <th>Localização</th>
            <th>Buscas</th>
        </tr>
        <?php foreach ($sessoes as $s): ?>
            <tr>
                <td><?= $s['sessao_id'] ?></td>
                <td><?= $s['inicio'] ?></td>
                <td><?= formatarTempo($s['duracao_segundos']) ?></td>
                <td><?= $s['lat'] && $s['lng'] ? "{$s['lat']}, {$s['lng']}" : '-' ?></td>
                <td>
                    <?php foreach (($buscasPorSessao[$s['sessao_id']] ?? []) as $b): ?>
                        <span class="badge"><?= htmlspecialchars($b) ?></span>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>🔥 Heatmap</h2>
    <div id="heatmap"></div>

    <h2>📊 Gráficos de Acesso</h2>
    <div class="charts-container">
        <div class="chart-card">
            <canvas id="chartDia"></canvas>
        </div>
        <div class="chart-card">
            <canvas id="chartSemana"></canvas>
        </div>
        <div class="chart-card">
            <canvas id="chartMes"></canvas>
        </div>
        <div class="chart-card">
            <canvas id="chartDiaSemana"></canvas>
        </div>
    </div>

    <script>
        const heatmapData = <?= json_encode($heatmap) ?>.map(p =>
            new google.maps.LatLng(p.lat, p.lng)
        );

        const map = new google.maps.Map(document.getElementById('heatmap'), {
            zoom: 12,
            center: { lat: -23.9608, lng: -46.3331 }
        });

        new google.maps.visualization.HeatmapLayer({
            data: heatmapData,
            radius: 25
        }).setMap(map);



        // Dados vindos do PHP
        const dadosDia = <?= json_encode($porDia) ?>;
        const dadosSemana = <?= json_encode($porSemana) ?>;
        const dadosMes = <?= json_encode($porMes) ?>;
        const dadosDiaSemana = <?= json_encode($porDiaSemana) ?>;

        // Função genérica para criar gráfico de barras
        function criarGraficoBarra(ctx, labels, data, titulo) {
            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: titulo,
                        data: data,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false }, title: { display: true, text: titulo } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        // Criando gráficos
        criarGraficoBarra(document.getElementById('chartDia'), Object.keys(dadosDia), Object.values(dadosDia), 'Acessos por Dia');
        criarGraficoBarra(document.getElementById('chartSemana'), Object.keys(dadosSemana), Object.values(dadosSemana), 'Acessos por Semana');
        criarGraficoBarra(document.getElementById('chartMes'), Object.keys(dadosMes), Object.values(dadosMes), 'Acessos por Mês');
        criarGraficoBarra(document.getElementById('chartDiaSemana'), Object.keys(dadosDiaSemana), Object.values(dadosDiaSemana), 'Dia da Semana Mais Acessado');
    </script>

</body>

</html>