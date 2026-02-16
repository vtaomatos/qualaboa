<?php

require_once __DIR__ . '/../autoload.php';

use App\Container;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');

$dataFiltro = $_GET['data'] ?? date('Y-m-d');
$horaFiltro = $_GET['hora'] ?? '00:00';

$dataFormatada = date('d/m/Y', strtotime($dataFiltro));

function debugQuery($sql, $params)
{
    foreach ($params as $p) {
        $p = is_numeric($p) ? $p : "'" . addslashes($p) . "'";
        $sql = preg_replace('/\?/', $p, $sql, 1);
    }
    return $sql;
}

$sessaoId = $_SESSION['id'] ?? $_GET['sessao_id'] ?? '';
$categoriaService = Container::categoriaService();
$categorias = $categoriaService->listarCategoriasGrandes();
$categoriasFormatadas = [];

foreach ($categorias as $categoria) {
    $categoriasFormatadas[$categoria['categoria_grande_nome']] =
        $categoria['cor_mapa'] ?: 'gray';
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Qual a boa?! Eventos na Região</title>
    <link rel="icon" type="image/x-icon" href="./favicon_io/favicon.ico" />

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fonte e CSS personalizado -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/main.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
</head>

<body class="text-center">

    <!-- Modal de Introdução -->
    <div class="modal fade show" id="modalIntro" tabindex="-1" style="display:block; background:rgba(0,0,0,.6)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start">
                <div class="modal-header">
                    <h5 class="modal-title">O que é o Qual a Boa?!</h5>
                </div>
                <div class="modal-body">
                    <p>
                        Aqui você encontra <strong>eventos que estão acontecendo hoje na sua região</strong>,
                        organizados no mapa em tempo real.
                    </p>
                    <ul>
                        <li>Filtre por tipo de evento</li>
                        <li>Veja onde está acontecendo</li>
                        <li>Use a IA para descobrir rolês do seu estilo</li>
                    </ul>
                    <p class="mt-3 text-center text-muted small">
                        O sistema está em construção 🚧 e a IA está aprendendo. <br>
                        Obrigado pela paciência! 😅
                    </p>
                    <p class="mb-0">
                        Ao continuar, vamos pedir sua localização para mostrar eventos próximos de você.
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary w-100" onclick="fecharIntro()" autofocus>Entendi, mostrar
                        eventos</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Região 2: Filtro de categorias + Mapa -->
    <div id="regiao-2">

        <div id="layout-principal">

            <!-- AGENDA -->
            <div id="agenda-lateral">

                <div class="agenda-header">
                    <button id="btnSemanaAnterior" class="btn btn-sm btn-outline-secondary">
                        ←
                    </button>

                    <h6 id="tituloSemana" class="m-0"></h6>

                    <button id="btnProximaSemana" class="btn btn-sm btn-outline-secondary">
                        →
                    </button>
                </div>


                <div id="diasSemana" class="dias-semana"></div>

                <div id="modalEventosDia" class="modal-dia">
                    <div class="modal-dia-header">
                        <h5>Eventos do dia</h5>
                        <button onclick="fecharModalEventosDia()">×</button>
                    </div>
                    <div id="modalEventosDiaBody" class="modal-dia-body"></div>
                </div>


                <div id="listaEventosDia" class="lista-eventos-dia"></div>

            </div>

            <!-- MAPA -->
            <div id="map-container">
                <div id="filtro-categorias" class="card shadow minimizado-lateralmente-direita">
                    <div class="card-header d-flex justify-content-between align-items-center p-2">
                        <label id="title-categorias" for="btn-toggle-categorias" style="cursor:pointer">
                            <strong>
                                Categorias
                            </strong>
                        </label>
                        <button id="btn-toggle-categorias" class="btn btn-sm btn-outline-primary col-5"
                            onclick="toggleRegiao('categorias-body', this)">
                            ✖
                        </button>
                    </div>
                    <div id="categorias-body" class="card-body p-2">
                        <?php foreach ($categorias as $cat): ?>
                            <div class="form-check">
                                <input id="categoria-<?= $cat['categoria_grande_id'] ?>"
                                    class="form-check-input filtro-categoria" type="checkbox"
                                    data-value="<?= $cat['categoria_grande_nome'] ?>"
                                    value="<?= $cat['categoria_grande_id'] ?>">
                                <label class="form-check-label" for="categoria-<?= $cat['categoria_grande_id'] ?>">
                                    <img
                                        src="https://maps.google.com/mapfiles/ms/icons/<?= $cat['cor_mapa'] ?? 'gray' ?>-dot.png">
                                    <?= $cat['categoria_grande_nome'] ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="ajuda-flutuante"
                    onclick="window.open('feedbacks.php?sessao_id=<?= urlencode($sessaoId) ?>', '_blank')"
                    title="Ajuda">
                    ?
                </div>
                <div id="map"></div>
            </div>

        </div>

    </div>

    <!-- Região 3: Chat com IA -->
    <div id="regiao-3" class="fixo-rodape">
        <div id="chat-area" class="col-12 pd-2 chat-area regiao-colapsada">
            <div id="chatInput" class="chat-input">
                <span id="dia-chat">Dia: <?= $dataFormatada ?></span>
                <input class="form-control mt-3" type="text" id="userInput"
                    placeholder="Ex: IA, quero curtir samba à noite." />
                <button id="btnEnviar" class="btn btn-secondary btn-chat col-12 mt-2"
                    onclick="enviarPergunta()">Enviar</button>
            </div>
            <div id="recomendacoesChat" class="recomendacoes-container"></div>
            <p id="quotaInfo"></p>
            <p id="explicacaoIA" class="explicacao-chat card-ia"></p>
        </div>
        <button class="toggle-btn" onclick="toggleRegiao('chat-area', this)">
            <!-- ↑ Busca com IA no dia <?= $dataFormatada ?> -->
            ↑ Busca com IA no dia
        </button>
    </div>

    <div id="lightbox" onclick="fecharLightbox()">
        <img id="lightbox-img" src="" alt="Imagem Ampliada" />
    </div>

    <div class="modal fade" id="modalEvento" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Evento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalEventoBody"></div>
            </div>
        </div>
    </div>


    <script>
        window.APP_CONFIG = {
            sessao_id: "<?= htmlspecialchars($sessaoId) ?>",
            dataFiltroFormatada: "<?= htmlspecialchars($dataFormatada) ?>",
            horaFiltro: "<?= htmlspecialchars($horaFiltro) ?>",
            categorias: <?= json_encode($categoriasFormatadas) ?>
        };

        const BASE_URL = "<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>";
    </script>

    <script src="js/state.js"></script>
    <script src="js/sessao.js"></script>
    <script src="js/quota.js"></script>
    <script src="js/eventos.js"></script>
    <script src="js/mapa.js"></script>
    <script src="js/chat.js"></script>
    <script src="js/agenda.js"></script>
    <script src="js/main.js"></script>
    <script src="js/layout.js"></script>



    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&callback=onGoogleMapsLoaded&loading=async"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

</body>

</html>