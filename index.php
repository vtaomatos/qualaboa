<?php
require_once './config/db.php';

try {
  $stmt = $conn->prepare('SELECT id FROM eventos LIMIT 1');
} catch (PDOException $e) {
  echo "Erro: " . $e->getMessage();
}

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

// Filtro por data
date_default_timezone_set('America/Sao_Paulo');
$dataFiltro = $_GET['data'] ?? date('Y-m-d');
$horaFiltro = $_GET['hora'] ?? '00:00';
$dataFormatada = htmlspecialchars($_GET['data'] ?? date('d/m/Y'));

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


$inPlaceholders = implode(',', array_fill(0, count($categoriasTecnicas), '?'));

$sql = "
SELECT 
  id, titulo, data_evento, descricao, flyer_html, endereco,
  categoria, latitude, longitude, instagram, linkInstagram
FROM eventos
WHERE DATE(data_evento) = ?
  AND TIME(data_evento) >= ?
  AND categoria IN ($inPlaceholders)
";

$params = array_merge(
  [$dataFiltro, $horaFiltro],
  $categoriasTecnicas
);

$stmt = $conn->prepare($sql);
$stmt->execute($params);


$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

//fazer de para para categorias

function debugQuery($sql, $params)
{
  foreach ($params as $p) {
    $p = is_numeric($p) ? $p : "'" . addslashes($p) . "'";
    $sql = preg_replace('/\?/', $p, $sql, 1);
  }
  return $sql;
}

foreach ($eventos as &$evento) {
  $evento['categoria'] = categoriaGrande($evento['categoria'], $CATEGORIAS_MAPA);
}

function categoriaGrande(string|null $categoriaTecnica, array $mapa): string
{
  foreach ($mapa as $categoriaGrande => $listaTecnica) {
    if (in_array($categoriaTecnica, $listaTecnica, true)) {
      return $categoriaGrande;
    }
  }
  return 'Outros / Não identificado';
}


function normalizarCategoria(?string $categoria): string
{
  $mapa = [
    // Música / Festas / Bares
    'MUSICA' => 'Música / Festas / Bares',
    'FESTA' => 'Música / Festas / Bares',
    'SHOW' => 'Música / Festas / Bares',
    'BAR' => 'Música / Festas / Bares',
    'BALADA' => 'Música / Festas / Bares',

    // Cultura / Arte / Teatro / Religiosos
    'ARTE' => 'Cultura / Arte / Teatro / Religiosos',
    'CULTURA' => 'Cultura / Arte / Teatro / Religiosos',
    'RELIGIOSO' => 'Cultura / Arte / Teatro / Religiosos',

    // Geek / Educação / Workshops
    'GEEK' => 'Geek / Educação / Workshops',
    'EDUCACAO' => 'Geek / Educação / Workshops',
    'WORKSHOP' => 'Geek / Educação / Workshops',
    'PALESTRA' => 'Geek / Educação / Workshops',

    // Esporte / Atividade Física
    'ATIVIDADE FISICA' => 'Esporte / Atividade Física',
    'ESPORTE' => 'Esporte / Atividade Física',

    // Outros
    'OUTROS' => 'Outros / Não identificado',
    'NAO IDENTIFICADO' => 'Outros / Não identificado',
  ];

  return $mapa[$categoria] ?? 'Outros / Não identificado';
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
          <button class="btn btn-primary w-100" onclick="fecharIntro()" autofocus>Entendi, mostrar eventos</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Região 1: Filtro de data e hora -->
  <div id="regiao-1" class="fixo-topo">
    <form id="form-regiao-1" method="GET" class="container-fluid pb-1 pt-1 regiao-colapsada">
      <label class="col-7">Data: <input class="form-control" type="date" name="data"
          value="<?= htmlspecialchars($dataFiltro) ?>" /></label>
      <label class="col-5">Hora: <input class="form-control" type="time" name="hora"
          value="<?= htmlspecialchars($horaFiltro) ?>" /></label>
      <button type="submit" class="col-12 mt-2 btn btn-primary buscar-data">Buscar</button>
    </form>
    <button class="toggle-btn" onclick="toggleRegiao('form-regiao-1', this)">
      ↓ Clique e encontre eventos na sua região por data: <?= $dataFormatada ?>
    </button>
  </div>

  <!-- Região 2: Filtro de categorias + Mapa -->
  <div id="regiao-2">
    <div class="ajuda-flutuante" onclick="window.open('feedbacks.php', '_blank')" title="Ajuda">
      ?
    </div>

    <div id="filtro-categorias" class="card shadow minimizado-lateralmente-direita">
      <div class="card-header d-flex justify-content-between align-items-center p-2">
        <label for="btn-toggle-categorias" style="cursor:pointer"><strong>Categorias</strong></label>
        <button id="btn-toggle-categorias" class="btn btn-sm btn-outline-primary col-5"
          onclick="toggleRegiao('categorias-body', this)">
          ☰
        </button>
      </div>
      <div id="categorias-body" class="card-body p-2 regiao-colapsada">
        <?php foreach ($categoriasSelecionadas as $cat): ?>
          <div class="form-check">
            <input id="categoria-<?= $cat ?>" class="form-check-input filtro-categoria" type="checkbox"
              value="<?= $cat ?>">
            <label class="form-check-label" for="categoria-<?= $cat ?>">
              <img src="https://maps.google.com/mapfiles/ms/icons/<?= $CATEGORIAS_CORES[$cat] ?? 'gray' ?>-dot.png">
              <?= $cat ?>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
    </div>


    <div id="margin-mapa"></div>
    <div id="map" class="mt-2 mb-2"></div>
  </div>

  <!-- Região 3: Chat com IA -->
  <div id="regiao-3" class="fixo-rodape">
    <div id="chat-area" class="col-12 pd-2 chat-area regiao-colapsada">
      <div id="chatInput" class="chat-input">
        <input class="form-control mt-3" type="text" id="userInput" placeholder="Ex: IA, quero curtir samba à noite." />
        <button id="btnEnviar" class="btn btn-secondary btn-chat col-12 mt-2" onclick="enviarPergunta()">Enviar</button>
      </div>
      <div id="recomendacoesChat" class="recomendacoes-container"></div>
      <p id="quotaInfo"></p>
      <p id="explicacaoIA" class="explicacao-chat card-ia"></p>
    </div>
    <button class="toggle-btn" onclick="toggleRegiao('chat-area', this)">
      ↑ Clique e use IA para encontrar eventos no dia <?= $dataFormatada ?>
    </button>
  </div>

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
            if (!data.imagem) return;
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
      const data = document.querySelector("input[name='data']").value;
      const hora = document.querySelector("input[name='hora']").value;
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

  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&callback=initMap"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

</body>

</html>