<?php
require_once './config/db.php';

try {
  $stmt = $conn->prepare('SELECT id FROM eventos LIMIT 1');
} catch (PDOException $e) {
  echo "Erro: " . $e->getMessage();
}

// Filtro por data
date_default_timezone_set('America/Sao_Paulo');
$dataFiltro = $_GET['data'] ?? date('Y-m-d');
$horaFiltro = $_GET['hora'] ?? '00:00';
$dataFormatada = htmlspecialchars($_GET['data'] ?? date('d/m/Y'));

$categoriasSelecionadas = $_GET['categorias'] ?? [
  'Música / Shows / Festas',
  'Cultura / Arte / Teatro',
  'Esporte / Atividade Física',
  'Educação / Workshops / Palestras',
  'Outros / Não identificado'
];

// Preparar e executar query
$sql = "
SELECT 
  id, titulo, data_evento, descricao, flyer_html, endereco, categoria, latitude, longitude, instagram, linkInstagram
FROM 
  eventos 
WHERE 
  DATE(data_evento) = :data 
  AND TIME(data_evento) >= :hora
  AND categoria IN ('" . implode("','", $categoriasSelecionadas) . "')
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':data', $dataFiltro);
$stmt->bindValue(':hora', $horaFiltro);
$stmt->execute();

$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <div id="filtro-categorias" class="card shadow">
      <div class="card-body p-2">
        <strong>Categorias</strong>
        <?php
        $categorias = [
          'Música / Shows / Festas',
          'Cultura / Arte / Teatro',
          'Esporte / Atividade Física',
          'Educação / Workshops / Palestras',
          'Outros / Não identificado'
        ];
        foreach ($categorias as $cat):
          ?>
          <div class="form-check">
            <input class="form-check-input filtro-categoria" type="checkbox" value="<?= $cat ?>" checked>
            <label class="form-check-label"><?= $cat ?></label>
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
        <button class="btn btn-secondary btn-chat col-12 mt-2" onclick="enviarPergunta()">Enviar</button>
      </div>
      <div id="recomendacoesChat" class="recomendacoes-container"></div>
      <p id="explicacaoIA" class="explicacao-chat"></p>
    </div>
    <button class="toggle-btn" onclick="toggleRegiao('chat-area', this)">
      ↑ Clique e use IA para encontrar eventos no dia <?= $dataFormatada ?>
    </button>
  </div>

  <div id="lightbox" onclick="fecharLightbox()">
    <img id="lightbox-img" src="" alt="Imagem Ampliada" />
  </div>

  <script>
    const dataFiltro = "<?= $dataFormatada ?>";
    const eventos = <?= json_encode($eventos) ?>;
    const eventosids = eventos.map(ev => ev.id);

    let ordemPrioridade = [];
    let map, markers = [], infoWindow;

    // Pins coloridos por categoria
    const categoriaPins = {
      "Música / Shows / Festas": "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
      "Cultura / Arte / Teatro": "https://maps.google.com/mapfiles/ms/icons/purple-dot.png",
      "Esporte / Atividade Física": "https://maps.google.com/mapfiles/ms/icons/green-dot.png",
      "Educação / Workshops / Palestras": "https://maps.google.com/mapfiles/ms/icons/blue-dot.png",
      "Outros / Não identificado": "https://maps.google.com/mapfiles/ms/icons/yellow-dot.png"
    };

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
      if (index === 0) return 50;
      if (index === 1) return 40;
      if (index === 2) return 35;
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
      const mostrarTexto = regiaoId === 'chat-area' ? `↑ Clique e use IA para encontrar eventos no dia ${dataFiltro}` : `↓ Clique e encontre eventos na sua região por data: ${dataFiltro}`;
      const ocultarTexto = regiaoId === 'chat-area' ? '↓ Ocultar' : '↑ Ocultar';
      btn.innerHTML = colapsada ? mostrarTexto : ocultarTexto;
    }

    // =================== Função enviar pergunta IA ===================
    function enviarPergunta() {
      const pergunta = document.getElementById("userInput").value;
      const data = document.querySelector("input[name='data']").value;
      const hora = document.querySelector("input[name='hora']").value;
      if (!pergunta.trim()) return;

      document.getElementById("explicacaoIA").textContent = "⏳ Pensando...";

      fetch("/api/chat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ pergunta, data, hora, eventos_id: eventos.map(ev => ev.id) })
      })
        .then(res => res.json())
        .then(data => {
          
          if (data.erro) {
            document.getElementById("explicacaoIA").innerHTML = `<span style="color:red;">⚠️ ${data.erro} <br> ${data.codigo ? `Código: ${data.codigo}` : ''}</span>`;
            document.getElementById("recomendacoesChat").innerHTML = "";
            return;
          }

          let resposta;
          try { 
            resposta = JSON.parse(data.resposta); 
            resposta = resposta.resposta || resposta; // lidar com estrutura { resposta: { ordem: [...], explicacao: "..." } }
            resposta.ordem = (resposta.ordem || []).map(id => parseInt(id)); 
          } catch { 
            resposta = { 
              ordem: [], explicacao: data.resposta 
            };
          }
          ordemPrioridade = resposta.ordem || [];
          document.getElementById("explicacaoIA").innerHTML = resposta.explicacao || "";
          document.getElementById("recomendacoesChat").innerHTML = "";
          initMap();
          document.scrollingElement.scrollTo(0, 999999);
        });
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
        const exemplo = eventosDoLocal[0];
        const pos = { lat: parseFloat(exemplo.latitude), lng: parseFloat(exemplo.longitude) };
        const tamanho = getTamanhoPorPrioridade(exemplo.id);
        const marker = criarMarker(map, pos, exemplo.titulo, categoriaPins[exemplo.categoria] || categoriaPins["Outros / Não identificado"], tamanho);
        markers.push(marker);

        marker.addListener("click", () => {
          infoWindow.setContent(criarSliderEventos(eventosDoLocal));
          infoWindow.open(map, marker);
          map.panBy(0, -250);
          google.maps.event.addListenerOnce(infoWindow, 'domready', () => {
            new Swiper('.swiper-container', { pagination: { el: '.swiper-pagination', clickable: true } });
            carregarImagensEventos(eventosDoLocal);
          });
          google.maps.event.addListenerOnce(infoWindow, 'closeclick', () => { map.panBy(0, 250); });
        });
      });
    }

    // =================== Lightbox ===================
    window.abrirLightbox = img => { document.getElementById('lightbox-img').src = img.src; document.getElementById('lightbox').style.display = 'flex'; };
    window.fecharLightbox = () => { document.getElementById('lightbox').style.display = 'none'; };

    // =================== Eventos DOM ===================
    document.addEventListener("DOMContentLoaded", () => { initMap(); });
    document.querySelectorAll('.filtro-categoria').forEach(cb => cb.addEventListener('change', initMap));
  </script>

  <script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAfCUegcuOqp8VUDdwJeYt9EoIGh4T0zPs&callback=initMap"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

</body>

</html>