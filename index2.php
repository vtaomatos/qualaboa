<?php
require_once './config/db.php';

date_default_timezone_set('America/Sao_Paulo');

/*
|--------------------------------------------------------------------------
| 1. MAPA DE CATEGORIAS
|--------------------------------------------------------------------------
*/

$CATEGORIAS_MAPA = [
    'Cultura / Arte / Teatro / Religiosos' => [
        'ARTE',
        'CULTURA',
        'RELIGIOSO'
    ],
    'Música / Festas / Bares' => [
        'MUSICA',
        'FESTA',
        'SHOW',
        'BAR',
        'BALADA'
    ],
    'Geek / Educação / Workshops' => [
        'GEEK',
        'EDUCACAO',
        'WORKSHOP',
        'PALESTRA'
    ],
    'Esporte / Atividade Física' => [
        'ATIVIDADE FISICA',
        'ESPORTE'
    ],
    'Outros / Não identificado' => [
        'OUTROS',
        'NAO IDENTIFICADO',
        null
    ],
];

/*
|--------------------------------------------------------------------------
| 2. DEFINIÇÃO DA SEMANA
|--------------------------------------------------------------------------
*/

$dataBase = $_GET['data'] ?? date('Y-m-d');

$inicioSemana = date('Y-m-d 00:00:00', strtotime('monday this week', strtotime($dataBase)));
$fimSemana = date('Y-m-d 23:59:59', strtotime('sunday this week', strtotime($dataBase)));

/*
|--------------------------------------------------------------------------
| 3. CATEGORIAS SELECIONADAS
|--------------------------------------------------------------------------
*/

$categoriasSelecionadas = $_GET['categorias'] ?? array_keys($CATEGORIAS_MAPA);

$categoriasTecnicas = [];

foreach ($categoriasSelecionadas as $catGrande) {
    if (isset($CATEGORIAS_MAPA[$catGrande])) {
        $categoriasTecnicas = array_merge(
            $categoriasTecnicas,
            $CATEGORIAS_MAPA[$catGrande]
        );
    }
}

$categoriasTecnicas = array_unique($categoriasTecnicas);

/*
|--------------------------------------------------------------------------
| 4. QUERY DA SEMANA
|--------------------------------------------------------------------------
*/

$params = [
    ':inicioSemana' => $inicioSemana,
    ':fimSemana' => $fimSemana
];

$sqlCategorias = '';

if (!empty($categoriasTecnicas)) {
    $placeholders = [];
    foreach ($categoriasTecnicas as $index => $cat) {
        $key = ":cat$index";
        $placeholders[] = $key;
        $params[$key] = $cat;
    }

    $sqlCategorias = " AND categoria IN (" . implode(',', $placeholders) . ")";
}

$sql = "
SELECT 
    id,
    titulo,
    data_evento,
    data_fim_evento,
    TIME(data_evento) as horario,
    categoria,
    latitude,
    longitude
FROM eventos
WHERE 
    deletado = 0
    AND visivel = 1
    AND data_evento BETWEEN :inicioSemana AND :fimSemana
    $sqlCategorias
ORDER BY data_evento ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| 5. AGRUPAR EVENTOS POR DIA
|--------------------------------------------------------------------------
*/

$eventosPorDia = [];

foreach ($eventos as $evento) {

    $dia = date('Y-m-d', strtotime($evento['data_evento']));

    $evento['categoria_grande'] = categoriaGrande(
        $evento['categoria'],
        $CATEGORIAS_MAPA
    );

    $eventosPorDia[$dia][] = $evento;
}

/*
|--------------------------------------------------------------------------
| 6. FUNÇÕES AUXILIARES
|--------------------------------------------------------------------------
*/

function categoriaGrande(?string $categoriaTecnica, array $mapa): string
{
    foreach ($mapa as $categoriaGrande => $listaTecnica) {
        if (in_array($categoriaTecnica, $listaTecnica, true)) {
            return $categoriaGrande;
        }
    }

    return 'Outros / Não identificado';
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
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

        /* =========================
   LAYOUT PRINCIPAL
========================= */

        .layout-principal {
            display: grid;
            grid-template-columns: 480px 1fr;
            height: 100vh;
            overflow: hidden;
        }

        /* Responsivo */
        @media (max-width: 1200px) {
            .layout-principal {
                grid-template-columns: 420px 1fr;
            }
        }

        @media (max-width: 900px) {
            .layout-principal {
                grid-template-columns: 1fr;
                grid-template-rows: 50vh 50vh;
            }

            #agenda-cidade {
                order: 2;
            }

            #mapa-container {
                order: 1;
            }
        }

        /* =========================
   AGENDA
========================= */

        #agenda-cidade {
            background: #fff;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .agenda-header {
            padding: 16px;
            border-bottom: 1px solid #eee;
        }

        .agenda-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }

        .agenda-nav button {
            border: none;
            background: #000;
            color: #fff;
            padding: 4px 10px;
            border-radius: 6px;
        }

        /* Grid semana */
        .agenda-grid {
            padding: 12px;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }

        .agenda-dia {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 6px;
            cursor: pointer;
            min-height: 70px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .agenda-dia.ativo {
            background: #000;
            color: #fff;
        }

        /* Preview eventos */
        .agenda-preview {
            font-size: 11px;
            margin-top: 4px;
        }

        .agenda-preview span {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* =========================
   DETALHE DO DIA
========================= */

        .agenda-dia-detalhe {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .agenda-dia-topo {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .agenda-lista-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }

        .agenda-evento-item {
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #f2f2f2;
            cursor: pointer;
            transition: 0.2s;
        }

        .agenda-evento-item:hover {
            background: #e6e6e6;
        }

        /* =========================
   MAPA
========================= */

        #mapa-container {
            height: 100%;
        }

        #map {
            width: 100%;
            height: 100%;
        }
    </style>
</head>

<body>

    <!-- Modal de Introdução -->
    <div class="modal fade show" id="modalIntro" tabindex="-1" style="display:block; background:rgba(0,0,0,.6)">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-start">
                <div class="modal-header">
                    <h5 class="modal-title">O que é o Qual a Boa?!</h5>
                </div>
                <div class="modal-body">
                    <p>
                        Eventos organizados por semana, sincronizados com o mapa em tempo real.
                    </p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary w-100" onclick="fecharIntro()">Começar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================
        LAYOUT PRINCIPAL
    ========================== -->
    <div class="layout-principal">

        <!-- =========================
            AGENDA LATERAL
        ========================== -->
        <aside id="agenda-cidade">

            <div class="agenda-header">
                <h2>Agenda da Semana</h2>
                <div class="agenda-nav">
                    <button onclick="mudarSemana(-1)">◀</button>
                    <span id="agenda-mes-ano"></span>
                    <button onclick="mudarSemana(1)">▶</button>
                </div>
            </div>

            <!-- GRID SEMANA -->
            <div id="agenda-semana" class="agenda-grid"></div>

            <!-- LISTA COMPLETA DO DIA -->
            <div id="agenda-dia-detalhe" class="agenda-dia-detalhe d-none">
                <div class="agenda-dia-topo">
                    <button onclick="voltarParaSemana()">←</button>
                    <strong id="agenda-dia-titulo"></strong>
                </div>

                <div id="agenda-lista-eventos" class="agenda-lista-scroll"></div>
            </div>

        </aside>


        <!-- =========================
            MAPA
        ========================== -->
        <main id="mapa-container">
            <div id="map"></div>
        </main>

    </div>

    <!-- =========================
        CHAT IA (fixo rodapé)
    ========================== -->
    <div id="regiao-3" class="fixo-rodape">
        <div id="chat-area" class="chat-area regiao-colapsada">
            <input class="form-control mt-3" type="text" id="userInput" placeholder="Ex: Quero um samba hoje à noite" />
            <button id="btnEnviar" class="btn btn-secondary btn-chat col-12 mt-2"
                onclick="enviarPergunta()">Enviar</button>

            <div id="recomendacoesChat" class="recomendacoes-container"></div>
            <p id="quotaInfo"></p>
            <p id="explicacaoIA" class="explicacao-chat card-ia"></p>
        </div>

        <button class="toggle-btn" onclick="toggleRegiao('chat-area', this)">
            ↑ IA
        </button>
    </div>

    <!-- Lightbox -->
    <div id="lightbox" onclick="fecharLightbox()">
        <img id="lightbox-img" src="" alt="Imagem Ampliada" />
    </div>

    <script>
        const sessao_id = sessionStorage.getItem("sessao_id") || crypto.randomUUID();
        sessionStorage.setItem("sessao_id", sessao_id);

        const dataFiltro = "<?= $dataFormatada ?>";
        const eventos = <?= json_encode($eventos) ?>;
        const eventos_ids = eventos.map(ev => ev.id);

        let ordemPrioridade = [];
        let map, markers = [], infoWindow;

        const imgDefault = '/imagens/sem_imagem.jpg';


        // LISTA de cores para as categorias (pode ser expandida conforme novas categorias forem adicionadas)
        const categoriaPins = <?= json_encode($CATEGORIAS_CORES, JSON_UNESCAPED_UNICODE); ?>;

        function getPinUrl(cor) {
            if (!cor) cor = 'gray';
            return `https://maps.google.com/mapfiles/ms/icons/${cor}-dot.png`;
        }

        // =================== Funções básicas ===================
        function getFallbackLocation() {
            return { lat: -23.9608, lng: -46.3331 };
        }

        function criarMapa(center) {
            return new google.maps.Map(document.getElementById("map"), {
                zoom: 12,
                gestureHandling: "greedy",
                center,
                mapTypeControl: false,
                streetViewControl: false,
                zoomControl: true,
            });
        }

        function criarMarker(map, pos, titulo, iconUrl, tamanho = 30) {
            return new google.maps.Marker({
                map,
                position: pos,
                title: titulo,
                icon: { url: iconUrl, scaledSize: new google.maps.Size(tamanho, tamanho) }
            });
        }

        function getTamanhoPorPrioridade(id) {
            const index = ordemPrioridade.findIndex(item => parseInt(item) === parseInt(id));
            if (index === 0) return 60;
            if (index === 1) return 50;
            if (index === 2) return 40;
            return 30;
        }

        // =================== Modal de Introdução ===================
        function fecharIntro() {
            document.getElementById('modalIntro').style.display = 'none';
            document.body.classList.remove('modal-open');
            if (map) mostrarLocalizacaoComAnimacao(map);
        }

        // =================== Localização usuário ===================
        function mostrarLocalizacaoComAnimacao(map) {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition(pos => {
                const userLoc = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                const circulo = new google.maps.Circle({
                    strokeColor: "#1E90FF", strokeOpacity: 0.6, strokeWeight: 2,
                    fillColor: "#1E90FF", fillOpacity: 0.2, map, center: userLoc,
                    radius: pos.coords.accuracy || 50
                });
                map.setCenter(userLoc);
                let growing = true, currentRadius = circulo.getRadius();
                setInterval(() => {
                    currentRadius += growing ? 5 : -5;
                    circulo.setRadius(currentRadius);
                    if (currentRadius >= (pos.coords.accuracy + 30)) growing = false;
                    if (currentRadius <= pos.coords.accuracy) growing = true;
                }, 80);
            });
        }

        // =================== Slider InfoWindow ===================
        function criarSliderEventos(eventos) {
            if (!eventos.length) return '';
            let slides = eventos.map(ev => {
                const imgId = `flyer-${ev.id}`;
                return `<div class="swiper-slide" style="text-align:center">
        <br>
      <h3 style="font-size:16px; margin:6px">${ev.titulo}</h3>
      <div class="img-placeholder" data-evento-id="${ev.id}">
        <div class="flyer-wrapper">
          <div class="flyer-loader"><div class="spinner"></div><span>Carregando imagem…</span></div>
          <img id="${imgId}" class="flyer-img hidden" style="max-width:250px; max-height:300px; object-fit:contain;"
            alt="${ev.titulo}" onclick="abrirLightbox(this)"/>
        </div>
        <br>
      </div>
      ${ev.linkInstagram ? `<a href="${ev.linkInstagram}" target="_blank" style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#1E90FF;">${ev.instagram || 'Instagram'}</a>` : ''}
      <br>
      </div>`;
            }).join('');
            return `<div class="swiper-container" style="width:230px"><div class="swiper-wrapper">${slides}</div><div class="swiper-pagination"></div></div>`;
        }

        function carregarImagensEventos(eventos) {
            eventos.forEach(ev => {
                fetch(`/api/evento_flyer.php?id=${ev.id}`)
                    .then(r => r.json())
                    .then(data => {
                        if (!data.imagem) data.imagem = imgDefault;
                        const img = document.getElementById(`flyer-${ev.id}`);
                        if (!img) return;
                        img.src = data.imagem;
                        img.onload = () => { img.classList.remove('hidden'); const loader = img.previousElementSibling; if (loader) loader.remove(); }
                    });
            });
        }

        // =================== Agrupar eventos ===================
        function agruparEventosPorLocal(eventos) {
            const agrupados = {};
            eventos.forEach(ev => {
                const key = `${parseFloat(ev.latitude).toFixed(5)}_${parseFloat(ev.longitude).toFixed(5)}`;
                if (!agrupados[key]) agrupados[key] = [];
                agrupados[key].push(ev);
            });
            return agrupados;
        }

        // =================== Toggle regiões ===================
        function toggleRegiao(regiaoId, btn) {
            const regiao = document.getElementById(regiaoId);
            const colapsada = regiao.classList.toggle('regiao-colapsada');
            if (regiaoId === 'categorias-body') {
                btn.innerHTML = colapsada ? '☰' : '✖';
                colapsada ? regiao.classList.add('minimizado-lateralmente-direita') : regiao.classList.remove('minimizado-lateralmente-direita');
            } else if (regiaoId === 'chat-area') {
                btn.innerHTML = colapsada ? `↑ Clique e use IA para encontrar eventos no dia ${dataFiltro}` : '↓ Ocultar';
            } else if (regiaoId === 'form-regiao-1') {
                btn.innerHTML = colapsada ? `↓ Clique e encontre eventos na sua região por data: ${dataFiltro}` : '↑ Ocultar';
            }
        }

        function normalizarDataHora(texto) {
            if (!texto) return texto;

            // Data americana -> brasileira (2026-02-06 → 06/02/2026)
            texto = texto.replace(
                /\b(\d{4})-(\d{2})-(\d{2})\b/g,
                (_, ano, mes, dia) => `${dia}/${mes}/${ano}`
            );

            // Hora com segundos -> sem segundos (19:00:00 → 19:00)
            texto = texto.replace(
                /\b(\d{2}:\d{2}):\d{2}\b/g,
                '$1'
            );

            return texto;
        }

        function parseRespostaIA(payload) {
            if (!payload) {
                return { ordem: [], explicacao: '' };
            }

            // Caso novo: backend já envia objeto
            if (typeof payload === 'object') {
                return {
                    ordem: Array.isArray(payload.ordem) ? payload.ordem.map(Number) : [],
                    explicacao: payload.explicacao || ''
                };
            }

            // Caso antigo: string JSON
            if (typeof payload === 'string') {
                try {
                    const parsed = JSON.parse(payload);
                    return parseRespostaIA(parsed.resposta || parsed);
                } catch {
                    return { ordem: [], explicacao: payload };
                }
            }

            return { ordem: [], explicacao: '' };
        }

        function melhorarHTMLIA(html) {
            if (!html) return '';

            // Converter "1. Texto" → <li>Texto</li>
            html = html.replace(
                /\d+\.\s\*\*(.*?)\*\*:/g,
                '<li><strong>$1</strong>'
            );

            // Converter Data
            html = html.replace(/\*\*Data\*\*/g, '📅');

            // Converter Local
            html = html.replace(/\*\*Local\*\*/g, '📍');

            return html;
        }


        // =================== Função enviar pergunta IA ===================
        async function enviarPergunta() {
            const quota = await carregarQuota();

            if (!quota || quota.disponiveis <= 0) {
                bloquearEnvio();
                return;
            }

            bloquearEnvio();

            const pergunta = document.getElementById("userInput").value;
            const data = diaSelecionado.toISOString().slice(0, 10);
            const hora = document.getElementById('filtro-hora')
                ? document.getElementById('filtro-hora').value
                : '00:00';

            if (!pergunta.trim()) return;

            document.getElementById("explicacaoIA").textContent = "⏳ Pensando...";

            fetch("/api/chat.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Session-Id": sessao_id
                },
                body: JSON.stringify({ pergunta, data, hora, eventos_id: eventos_ids })
            })
                .then(res => res.json())
                .then(data => {

                    if (data.erro) {
                        document.getElementById("explicacaoIA").innerHTML = `<span style="color:red;">⚠️ ${data.erro} <br> ${data.codigo ? `Código: ${data.codigo}` : ''}</span>`;
                        document.getElementById("recomendacoesChat").innerHTML = "";
                        return;
                    }

                    const resposta = parseRespostaIA(data.resposta);

                    liberarEnvio();
                    carregarQuota();

                    ordemPrioridade = resposta.ordem || [];

                    let explicacaoFormatada = normalizarDataHora(resposta.explicacao || "");
                    explicacaoFormatada = melhorarHTMLIA(explicacaoFormatada);

                    document.getElementById("explicacaoIA").innerHTML = explicacaoFormatada;
                    document.getElementById("recomendacoesChat").innerHTML = "";

                    initMap();
                    document.scrollingElement.scrollTo(0, 999999);
                });
        }

        function criarBotaoFechar(infoWindow, map) {
            // Cria o botão
            const btnFechar = document.createElement('button');
            btnFechar.textContent = 'X';
            btnFechar.style.position = 'absolute';
            btnFechar.style.top = '10px';
            btnFechar.style.right = '10px';
            btnFechar.style.width = '36px';
            btnFechar.style.height = '36px';
            btnFechar.style.border = 'none';
            btnFechar.style.borderRadius = '50%';
            btnFechar.style.background = 'rgba(255,255,255,0.5)';
            btnFechar.style.cursor = 'pointer';
            btnFechar.style.zIndex = '1000';
            btnFechar.style.fontSize = '20px';
            btnFechar.style.lineHeight = '36px';
            btnFechar.style.textAlign = 'center';
            btnFechar.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
            btnFechar.style.color = '#444';
            //margin 0
            //padding 0
            btnFechar.style.margin = '0';
            btnFechar.style.padding = '0';

            // Fecha o InfoWindow ao clicar
            btnFechar.addEventListener('click', () => {
                infoWindow.close();
                map.panBy(0, 250); // Ajusta o mapa se necessário
            });

            return btnFechar;
        }


        // =================== Inicializar mapa ===================
        function initMap() {
            if (!map) map = criarMapa(getFallbackLocation());
            markers.forEach(m => m.setMap(null));
            markers = [];

            const categoriasAtivas = Array.from(document.querySelectorAll('.filtro-categoria:checked')).map(i => i.value);
            const eventosFiltrados = categoriasAtivas.length ? eventos.filter(ev => categoriasAtivas.includes(ev.categoria)) : eventos;

            const agrupados = agruparEventosPorLocal(eventosFiltrados);

            if (!infoWindow) infoWindow = new google.maps.InfoWindow({ disableAutoPan: false });

            Object.keys(agrupados).forEach(key => {
                const eventosDoLocal = agrupados[key];
                // Tenta pegar um evento que não seja "Outros / Não identificado"
                const exemplo = eventosDoLocal.find(ev => ev.categoria !== "Outros / Não identificado") || eventosDoLocal[0];
                const pos = { lat: parseFloat(exemplo.latitude), lng: parseFloat(exemplo.longitude) };
                const tamanho = getTamanhoPorPrioridade(exemplo.id);
                const marker = criarMarker(map, pos, exemplo.titulo, getPinUrl(categoriaPins[exemplo.categoria] || categoriaPins["Outros / Não identificado"]), tamanho);
                markers.push(marker);

                marker.addListener("click", () => {
                    infoWindow.setContent(criarSliderEventos(eventosDoLocal));
                    infoWindow.open(map, marker);
                    map.panBy(0, -250);

                    google.maps.event.addListenerOnce(infoWindow, 'domready', () => {
                        // Inicializa o swiper
                        new Swiper('.swiper-container', { pagination: { el: '.swiper-pagination', clickable: true } });
                        carregarImagensEventos(eventosDoLocal);

                        // Cria o botão e adiciona dentro do InfoWindow
                        const iwContainer = document.querySelector('#map .gm-style-iw-ch, #map .gm-style-iw-chr');
                        if (iwContainer && !iwContainer.querySelector('.custom-close-btn')) {
                            const btn = criarBotaoFechar(infoWindow, map);
                            btn.classList.add('custom-close-btn'); // só pra garantir que não duplica
                            iwContainer.appendChild(btn);
                        }
                    });

                    google.maps.event.addListenerOnce(infoWindow, 'closeclick', () => {
                        map.panBy(0, 250);
                    });
                });

            });
        }

        /* =================== 
          CONTROLE DE QUOTA 
        =================== */

        async function carregarQuota() {
            if (!sessao_id) return null;

            try {
                const res = await fetch('/api/quota.php', {
                    headers: {
                        'X-Session-Id': sessao_id
                    }
                });

                const data = await res.json();

                atualizarUIQuota(data);
                return data;

            } catch (e) {
                console.error('Erro ao carregar quota', e);
                return null;
            }
        }

        function formatarTempo(segundos) {
            if (segundos <= 0) return 'agora';

            const h = Math.floor(segundos / 3600);
            const m = Math.floor((segundos % 3600) / 60);

            if (h > 0) return `${h}h ${m}min`;
            return `${m}min`;
        }

        function atualizarUIQuota(quota) {
            const el = document.getElementById('quotaInfo');

            if (!quota || quota.erro) {
                el.textContent = '';
                liberarEnvio();
                return;
            }

            if (quota.disponiveis > 0) {
                el.innerHTML = `💬 Você tem ${quota.disponiveis} de ${quota.limite} prompts disponíveis`;
                liberarEnvio();
            } else {
                el.innerHTML = `⏳ Limite de ${quota.limite} prompts atingido. Volta em ${formatarTempo(quota.reset_em_segundos)}`;
                bloquearEnvio();
            }
        }

        function bloquearEnvio() {
            document.getElementById('btnEnviar').disabled = true;
        }

        function liberarEnvio() {
            document.getElementById('btnEnviar').disabled = false;
        }

        document.addEventListener('DOMContentLoaded', () => {
            carregarQuota();
        });


        /* ===============================
           LOG DE SESSÃO E ACESSO (PROGRESSIVO)
        =============================== */

        let sessaoConfirmada = false;
        let acessoRegistrado = false;
        let tempoSessao = 0; // em segundos
        let ultimoHeartbeat = Date.now();

        let localizacaoUsuario = getFallbackLocation();

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                localizacaoUsuario = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude
                };
            });
        }

        function enviarLogSessao(evento) {
            const agora = Date.now();
            const delta = Math.round((agora - ultimoHeartbeat) / 1000);
            ultimoHeartbeat = agora;
            tempoSessao += delta;

            navigator.sendBeacon("/log_builders/log_sessao.php", JSON.stringify({
                evento,
                sessao_id,
                tempo: delta,
                rota: location.pathname + location.search,
                lat: localizacaoUsuario.lat,
                lng: localizacaoUsuario.lng
            }));

            return delta;
        }

        function registrarAcesso() {
            if (acessoRegistrado) return;
            acessoRegistrado = true;

            fetch('/log_builders/log_access.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tipo: 'user_interaction',
                    page: window.location.pathname + location.search,
                    sessao_id,
                    ts: Date.now()
                })
            });

            removerListenersIniciais();
        }

        function iniciarSessaoSeNecessario() {
            if (sessaoConfirmada) return;
            sessaoConfirmada = true;

            if (!sessionStorage.getItem("sessao_start")) {
                enviarLogSessao("start");
                sessionStorage.setItem("sessao_start", "1");
            }

            heartbeatProgressivo();
            registrarAcesso(); // dispara acesso junto na primeira interação
            removerListenersIniciais();
        }

        function heartbeatProgressivo() {
            enviarLogSessao("heartbeat");

            let proximoIntervalo;

            if (tempoSessao < 60) {            // 0-1 min → 5s
                proximoIntervalo = 5000;
            } else if (tempoSessao < 180) {    // 1-3 min → 15s
                proximoIntervalo = 15000;
            } else if (tempoSessao < 600) {    // 3-10 min → 30s
                proximoIntervalo = 30000;
            } else {                            // 10min+ → 1min
                proximoIntervalo = 60000;
            }

            setTimeout(heartbeatProgressivo, proximoIntervalo);
        }

        function removerListenersIniciais() {
            ["click", "scroll", "keydown", "touchstart"].forEach(e =>
                window.removeEventListener(e, iniciarSessaoSeNecessario)
            );
            ["click", "scroll", "keydown", "touchstart"].forEach(e =>
                window.removeEventListener(e, registrarAcesso)
            );
        }

        // inicializa sessão e acesso **apenas após ação do usuário**
        ["click", "scroll", "keydown", "touchstart"].forEach(e =>
            window.addEventListener(e, iniciarSessaoSeNecessario, { once: true })
        );
        ["click", "scroll", "keydown", "touchstart"].forEach(e =>
            window.addEventListener(e, registrarAcesso, { once: true })
        );

        // encerra heartbeat e registra "end" ao sair da página
        window.addEventListener("beforeunload", () => {
            enviarLogSessao("end");
        });


        // =================== Lightbox ===================
        window.abrirLightbox = img => { document.getElementById('lightbox-img').src = img.src; document.getElementById('lightbox').style.display = 'flex'; };
        window.fecharLightbox = () => { document.getElementById('lightbox').style.display = 'none'; };

        // =================== Eventos DOM ===================
        document.addEventListener("DOMContentLoaded", () => { initMap(); });
        document.querySelectorAll('.filtro-categoria').forEach(cb => cb.addEventListener('change', initMap));

        window.addEventListener('error', e => {
            console.error('Erro JS:', e.message, e.filename, e.lineno);
        });

    </script>

    <script>

        let semanaBase = new Date();
        let diaSelecionado = new Date();

        /* ===============================
           LIMITES DE NAVEGAÇÃO
        =================================*/

        const hoje = new Date();
        const limitePassado = new Date();
        limitePassado.setDate(hoje.getDate() - 7);

        const limiteFuturo = new Date();
        limiteFuturo.setMonth(hoje.getMonth() + 2);

        /* ===============================
           SEMANA
        =================================*/

        function mudarSemana(delta) {

            const novaSemana = new Date(semanaBase);
            novaSemana.setDate(novaSemana.getDate() + delta * 7);

            if (novaSemana < limitePassado || novaSemana > limiteFuturo) return;

            semanaBase = novaSemana;
            renderAgenda();
        }

        /* ===============================
           RENDER SEMANA
        =================================*/

        function renderAgenda() {

            const container = document.getElementById('agenda-semana');
            const detalhe = document.getElementById('agenda-dia-detalhe');

            container.classList.remove('d-none');
            detalhe.classList.add('d-none');

            container.innerHTML = '';

            const inicioSemana = new Date(semanaBase);
            inicioSemana.setDate(inicioSemana.getDate() - inicioSemana.getDay());

            const mesAno = inicioSemana.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
            document.getElementById('agenda-mes-ano').innerText = mesAno;

            for (let i = 0; i < 7; i++) {

                const dia = new Date(inicioSemana);
                dia.setDate(inicioSemana.getDate() + i);

                const dataISO = dia.toISOString().slice(0, 10);

                const eventosDia = eventos
                    .filter(ev => ev.data_evento?.startsWith(dataISO))
                    .sort((a, b) => new Date(a.data_evento) - new Date(b.data_evento));

                const div = document.createElement('div');
                div.className = 'agenda-dia';

                if (dataISO === diaSelecionado.toISOString().slice(0, 10)) {
                    div.classList.add('ativo');
                }

                const preview = eventosDia.slice(0, 3).map(ev => {
                    const hora = new Date(ev.data_evento).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                    return `<span>${hora} - ${ev.titulo}</span>`;
                }).join('');

                const restante = eventosDia.length > 3
                    ? `<span>+${eventosDia.length - 3} eventos</span>`
                    : '';

                div.innerHTML = `
            <strong>${dia.getDate()}</strong>
            <small>${dia.toLocaleDateString('pt-BR', { weekday: 'short' })}</small>
            <div class="agenda-preview">
                ${preview}
                ${restante}
            </div>
        `;

                div.onclick = () => {
                    diaSelecionado = dia;
                    abrirDia(dataISO);
                };

                container.appendChild(div);
            }
        }

        /* ===============================
           ABRIR DIA COMPLETO
        =================================*/

        function abrirDia(dataISO) {

            const container = document.getElementById('agenda-semana');
            const detalhe = document.getElementById('agenda-dia-detalhe');
            const lista = document.getElementById('agenda-lista-eventos');

            container.classList.add('d-none');
            detalhe.classList.remove('d-none');

            lista.innerHTML = '';

            const eventosDia = eventos
                .filter(ev => ev.data_evento?.startsWith(dataISO))
                .sort((a, b) => new Date(a.data_evento) - new Date(b.data_evento));

            document.getElementById('agenda-dia-titulo').innerText =
                new Date(dataISO).toLocaleDateString('pt-BR', {
                    weekday: 'long',
                    day: '2-digit',
                    month: 'long'
                });

            eventosDia.forEach(ev => {

                const hora = new Date(ev.data_evento)
                    .toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

                const item = document.createElement('div');
                item.className = 'agenda-evento-item';
                item.innerHTML = `<strong>${hora}</strong> - ${ev.titulo}`;

                item.onclick = () => verEventoNoMapa(ev);

                lista.appendChild(item);
            });

            aplicarFiltroMapa(dataISO);
        }

        /* ===============================
           VOLTAR
        =================================*/

        function voltarParaSemana() {
            renderAgenda();
        }

        /* ===============================
           FILTRO MAPA POR DIA
        =================================*/

        function aplicarFiltroMapa(dataISO) {

            const eventosDia = eventos.filter(ev =>
                ev.data_evento?.startsWith(dataISO)
            );

            window.eventosFiltradosAgenda = eventosDia;

            initMap();
        }

        /* ===============================
           MAPA AJUSTE (pequena extensão)
        =================================*/

        const initMapOriginal = initMap;

        initMap = function () {

            if (!map) map = criarMapa(getFallbackLocation());

            markers.forEach(m => m.setMap(null));
            markers = [];

            let baseEventos = window.eventosFiltradosAgenda || eventos;

            const categoriasAtivas = Array.from(
                document.querySelectorAll('.filtro-categoria:checked')
            ).map(i => i.value);

            if (categoriasAtivas.length) {
                baseEventos = baseEventos.filter(ev =>
                    categoriasAtivas.includes(ev.categoria)
                );
            }

            const agrupados = agruparEventosPorLocal(baseEventos);

            if (!infoWindow) infoWindow = new google.maps.InfoWindow({ disableAutoPan: false });

            Object.keys(agrupados).forEach(key => {

                const eventosDoLocal = agrupados[key];
                const exemplo = eventosDoLocal[0];

                const pos = {
                    lat: parseFloat(exemplo.latitude),
                    lng: parseFloat(exemplo.longitude)
                };

                const tamanho = getTamanhoPorPrioridade(exemplo.id);

                const marker = criarMarker(
                    map,
                    pos,
                    exemplo.titulo,
                    getPinUrl(categoriaPins[exemplo.categoria] || categoriaPins["Outros / Não identificado"]),
                    tamanho
                );

                markers.push(marker);

                marker.addListener("click", () => {
                    infoWindow.setContent(criarSliderEventos(eventosDoLocal));
                    infoWindow.open(map, marker);
                });
            });
        };

        /* ===============================
           INICIALIZA
        =================================*/

        document.addEventListener('DOMContentLoaded', () => {
            renderAgenda();
            aplicarFiltroMapa(diaSelecionado.toISOString().slice(0, 10));
        });

    </script>




    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&callback=initMap"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

</body>

</html>