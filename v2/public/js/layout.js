function reorganizarLayoutMobile() {
    const layoutPrincipal = document.getElementById('layout-principal');
    const agendaLateral = document.getElementById('agenda-lateral');
    const mapContainer = document.getElementById('map-container');
    const listaEventos = document.getElementById('listaEventosDia');

    const isMobile = window.innerWidth <= 1200;

    if (isMobile) {
        if (!agendaLateral.contains(mapContainer)) {
            // move mapa e lista para dentro da agenda
            agendaLateral.appendChild(mapContainer);
            agendaLateral.appendChild(listaEventos);
        }
    } else {
        if (!layoutPrincipal.contains(mapContainer)) {
            // volta mapa para lado a lado
            layoutPrincipal.appendChild(agendaLateral);
            layoutPrincipal.appendChild(mapContainer);
        } else {
            layoutPrincipal.innerHTML = '';
            layoutPrincipal.appendChild(agendaLateral);
            layoutPrincipal.appendChild(mapContainer);
        }
    }
}

function ajustarPaddingRodape() {
    const layout = document.getElementById('layout-principal');
    const rodape = document.querySelector('.fixo-rodape');
    if (layout && rodape) {
        const alturaRodape = rodape.offsetHeight;
        layout.style.paddingBottom = alturaRodape + 'px';
    }
}

function ajustaTextoBotaoSemana() {
    let btnProximaSemana = document.getElementById('btnProximaSemana');
    let btnSemanaAnterior = document.getElementById('btnSemanaAnterior');
    let botoesSemana = [btnSemanaAnterior, btnProximaSemana]
    botoesSemana.forEach((btn) => {
        if (btn.offsetWidth > 50) {
            btn.innerHTML = (btn.id === 'btnProximaSemana') ? '->' : '<-';
            btn.style.width = '50px';
        } else {
            btn.innerHTML = (btn.id === 'btnProximaSemana') ? '->' : '<-';
        }
    });
}


// Inicializa ao carregar
window.addEventListener('load', reorganizarLayoutMobile);
window.addEventListener('resize', reorganizarLayoutMobile);
window.addEventListener('load', ajustarPaddingRodape);
window.addEventListener('resize', ajustarPaddingRodape);
window.addEventListener('load', ajustaTextoBotaoSemana);
window.addEventListener('resize', ajustaTextoBotaoSemana);

