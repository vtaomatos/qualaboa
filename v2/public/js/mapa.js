App.initMap = function () {
    if (!App.state.map) {
        App.state.map = new google.maps.Map(
            document.getElementById("map"),
            {
                zoom: 12,
                center: { lat: -23.9608, lng: -46.3331 },
                fullscreenControl: false,
                zoomControl: false,
                mapTypeControl: false,
                scaleControl: false,
                streetViewControl: false,
                rotateControl: false,
                mapTypeId: 'satellite'
            }
        );
    }
};

App.atualizarMarkers = function () {
    const { map, eventos, categoriasCores, ordemPrioridade } = App.state;
    console.log("Mapa:", map);
    console.log("Eventos:", eventos?.length);
    console.log("FiltroData:", App.state.dataFiltroFormatada);
    console.log("FiltroEventoId:", App.state.filtroEventoId);
    console.log("CategoriasAtivas:", App.state.categoriasAtivas);


    if (!map) return;

    // ===============================
    // 1️⃣ COMEÇA COM TODOS OS EVENTOS
    // ===============================
    let eventosFiltrados = [...eventos];

    // ===============================
    // 2️⃣ FILTRO POR DATA (AGENDA)
    // ===============================
    if (App.state.dataFiltroFormatada) {
        eventosFiltrados = eventosFiltrados.filter(ev =>
            ev.data_evento?.split(" ")[0] ===
            App.brToISO(App.state.dataFiltroFormatada)
        );
    }


    // ===============================
    // 3️⃣ FILTRO POR EVENTO ESPECÍFICO
    // ===============================
    if (App.state.filtroEventoId) {
        eventosFiltrados = eventosFiltrados.filter(ev =>
            ev.id == App.state.filtroEventoId
        );
    }

    // ===============================
    // 4️⃣ FILTRO POR CATEGORIA
    // ===============================
    const categoriasAtivas = App.state.categoriasAtivas || [];

    if (categoriasAtivas.length) {
        eventosFiltrados = eventosFiltrados.filter(ev =>
            categoriasAtivas.includes(String(ev.categoria_grande))
        );
    }

    // ===============================
    // LIMPA MARCADORES ANTIGOS
    // ===============================
    App.state.markers.forEach(m => m.setMap(null));
    App.state.markers = [];

    // ===============================
    // AGRUPAMENTO
    // ===============================
    const agrupados = {};

    eventosFiltrados.forEach(ev => {

        if (!ev.latitude || !ev.longitude) return;

        const key =
            `${parseFloat(ev.latitude).toFixed(5)}_${parseFloat(ev.longitude).toFixed(5)}`;

        if (!agrupados[key]) agrupados[key] = [];

        agrupados[key].push(ev);
    });

    // ===============================
    // INFOWINDOW ÚNICA
    // ===============================
    if (!App.state.infoWindow) {
        App.state.infoWindow =
            new google.maps.InfoWindow({ disableAutoPan: false });
    }

    // ===============================
    // CRIA MARKERS
    // ===============================
    Object.keys(agrupados).forEach(key => {

        const eventosDoLocal = agrupados[key];

        if (!eventosDoLocal.length) return;

        const exemplo =
            eventosDoLocal.find(ev =>
                ev.categoria_grande !== "Outros / Não identificado"
            ) || eventosDoLocal[0];

        const pos = {
            lat: parseFloat(exemplo.latitude),
            lng: parseFloat(exemplo.longitude)
        };

        const tamanho =
            App.getTamanhoPorPrioridade?.(exemplo.id) || 30;

        const cor =
            categoriasCores[exemplo.categoria_grande] ||
            categoriasCores["Outros / Não identificado"] ||
            "gray";

        const imagemPin =
            App.getPinUrl?.(cor) ||
            `https://maps.google.com/mapfiles/ms/icons/${cor}-dot.png`;

        const marker = new google.maps.Marker({
            map,
            position: pos,
            title: exemplo.titulo,
            icon: {
                url: imagemPin,
                scaledSize: new google.maps.Size(tamanho, tamanho)
            }
        });

        App.state.markers.push(marker);

        // CLICK DO MARKER
        marker.addListener("click", () => {

            App.state.infoWindow.setContent(
                App.criarSliderEventos?.(eventosDoLocal) || ""
            );

            App.state.infoWindow.open(map, marker);

            map.panBy(0, -250);

            google.maps.event.addListenerOnce(
                App.state.infoWindow,
                'domready',
                () => {

                    new Swiper('.swiper-container', {
                        pagination: {
                            el: '.swiper-pagination',
                            clickable: true
                        }
                    });

                    App.carregarImagensEventos?.(eventosDoLocal);

                    const iwContainer = document.querySelector(
                        '#map .gm-style-iw-ch, #map .gm-style-iw-chr'
                    );

                    if (
                        iwContainer &&
                        !iwContainer.querySelector('.custom-close-btn')
                    ) {
                        const btn =
                            App.criarBotaoFechar?.(App.state.infoWindow, map);

                        if (btn) {
                            btn.classList.add('custom-close-btn');
                            iwContainer.appendChild(btn);
                        }
                    }
                }
            );

            google.maps.event.addListenerOnce(
                App.state.infoWindow,
                'closeclick',
                () => map.panBy(0, 250)
            );
        });
    });
};


App.initFiltros = function () {

    document.addEventListener('change', (e) => {

        if (e.target.classList.contains('filtro-categoria')) {

            // Pega TODOS os checkboxes marcados
            const selecionados = Array.from(
                document.querySelectorAll('.filtro-categoria:checked')
            ).map(cb =>
                cb.dataset.value
            );

            console.log("selecionados", selecionados)

            App.state.categoriasAtivas = selecionados;

            App.atualizarMarkers();
            App.renderEventosDia()
        }
    });

};

App.criarSliderEventos = function (eventos) {
    if (!eventos.length) return '';
    let slides = eventos.map(ev => {
        const imgId = `flyer-${ev.id}`;
        return `<div class="swiper-slide" style="text-align:center">
        <br>
      <h3 style="font-size:16px; margin:6px">${ev.titulo}</h3>
      <div class="img-placeholder" data-evento-id="${ev.id}">
        <div class="flyer-wrapper">
          <div class="flyer-loader"><div class="spinner"></div><span>Carregando imagem…</span></div>
          <img id="${imgId}" class="flyer-img hidden" style="max-width:150px; max-height:50vh; object-fit:contain;"
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

App.carregarImagensEventos = function (eventos) {
    const imgDefault = '/imagens/sem_imagem.jpg';

    eventos.forEach(ev => {
        fetch(`${BASE_URL}/../api/evento_flyer.php?id=${ev.id}`)
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


App.getTamanhoPorPrioridade = function (id) {

    const ordem = App.state.ordemPrioridade || [];

    const index = ordem.findIndex(item =>
        parseInt(item) === parseInt(id)
    );

    if (index === 0) return 60;
    if (index === 1) return 50;
    if (index === 2) return 40;

    return 30;
};

App.getPinUrl = function (cor) {
    if (!cor) cor = 'gray';
    return `https://maps.google.com/mapfiles/ms/icons/${cor}-dot.png`;
}

App.criarBotaoFechar = function (infoWindow, map) {
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




document.addEventListener("eventosAtualizados", App.atualizarMarkers);

window.onGoogleMapsLoaded = function () {
    App.initMap();
};

document.addEventListener('DOMContentLoaded', () => {
    App.initFiltros();
});

window.abrirLightbox = img => { if (!img.src.includes("sem_imagem.jpg")) { document.getElementById('lightbox-img').src = img.src; document.getElementById('lightbox').style.display = 'flex'; } };
window.fecharLightbox = () => { document.getElementById('lightbox').style.display = 'none'; };


