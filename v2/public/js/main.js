window.onGoogleMapsLoaded = function () {
    App.initMap();

    const params = new URLSearchParams(window.location.search);
    const dataURL = params.get("data");

    // Coloque isso antes de usar dataHoje
    const hoje = new Date();
    const dataHoje = hoje.toISOString().split("T")[0]; // "YYYY-MM-DD"

    const dataInicial = dataURL || dataHoje;

    App.carregarEventosAPI(dataInicial);
};

document.addEventListener("DOMContentLoaded", () => {
    App.carregarQuota?.();
});



// =================== Modal de Introdução ===================
window.fecharIntro = function () {

    const modal = document.getElementById('modalIntro');

    if (modal) {
        modal.style.display = 'none';
    }

    document.body.classList.remove('modal-open');

    // Se o mapa já estiver inicializado
    if (window.App && App.state && App.state.map) {
        App.mostrarLocalizacaoComAnimacao(App.state.map);
    }
};

window.toggleRegiao = function (regiaoId, btn) {
    const regiao = document.getElementById(regiaoId);
    const colapsada = regiao.classList.toggle('regiao-colapsada');
    if (regiaoId === 'categorias-body') {
        const titleCategorias = document.getElementById('title-categorias');
        const filtroCategorias = document.getElementById('filtro-categorias')
        btn.innerHTML = colapsada ? '☰' : '✖';
        if (colapsada) {
            titleCategorias.style.display = 'none';
            filtroCategorias.style.backgroundColor = 'rgba(0,0,0,.1)';
        } else {
            titleCategorias.style.display = 'flex';
            filtroCategorias.style.backgroundColor = '#fff';
        }
    } else if (regiaoId === 'chat-area') {
        // btn.innerHTML = colapsada ? `↑ Busca com IA no dia ${App.state.dataFiltroFormatada.split(' ')[0]}` : '↓ Ocultar';
        btn.innerHTML = colapsada ? `↑ Busca com IA no dia` : '↓ Ocultar';
    }
}

// main.js


function ajustarPaddingRodape() {
    const layout = document.getElementById('layout-principal');
    const rodape = document.querySelector('.fixo-rodape');
    if (layout && rodape) {
        const alturaRodape = rodape.offsetHeight;
        layout.style.paddingBottom = alturaRodape + 'px';
    }
}

App.formatarDataBR = function (date) {
    let data;

    if (date instanceof Date) {
        data = date;
    } else if (typeof date === "string" && date.includes("-")) {
        const [ano, mes, dia] = date.split(" ")[0].split("-").map(Number);
        data = new Date(ano, mes - 1, dia);
    } else {
        data = new Date(date);
    }

    const dia = String(data.getDate()).padStart(2, '0');
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const ano = data.getFullYear();
    const hora = String(data.getHours()).padStart(2, '0');
    const minuto = String(data.getMinutes()).padStart(2, '0');

    return `${dia}/${mes}/${ano} ${hora}:${minuto}`;
};

App.brToISO = function (dataBR) {
    const [dia, mes, ano] = dataBR.split(" ")[0].split("/");
    return `${ano}-${mes}-${dia}`;
}

