App.state.dataInicioSemana = null;
App.state.diaSelecionado = null;



/* ===============================
   INICIALIZAÇÃO
=============================== */

App.initAgenda = function () {

    // 🔥 1️⃣ Pega data da URL (se existir)
    const params = new URLSearchParams(window.location.search);
    const dataUrl = params.get("data");

    let dataBase;

    if (dataUrl) {
        const [ano, mes, dia] = dataUrl.split("-");
        dataBase = new Date(ano, mes - 1, dia); // evita bug de timezone
    } else {
        dataBase = new Date();
    }

    // 🔥 2️⃣ Define início da semana com base nessa data
    App.state.dataInicioSemana = App.getInicioSemana(dataBase);

    App.renderSemana();

    function redirecionarComNovaData(dias) {

        const novaData = new Date(App.state.dataInicioSemana);
        novaData.setDate(novaData.getDate() + dias);

        const yyyy = novaData.getFullYear();
        const mm = String(novaData.getMonth() + 1).padStart(2, "0");
        const dd = String(novaData.getDate()).padStart(2, "0");

        const dataISO = `${yyyy}-${mm}-${dd}`;

        // Pega parâmetros atuais
        const params = new URLSearchParams(window.location.search);

        // Atualiza (ou cria) o parâmetro data
        params.set("data", dataISO);

        // Redireciona mantendo tudo
        window.location.search = params.toString();
    }


    document.getElementById("btnSemanaAnterior")
        .addEventListener("click", () => {
            redirecionarComNovaData(-7);
        });

    document.getElementById("btnProximaSemana")
        .addEventListener("click", () => {
            redirecionarComNovaData(7);
        });
};


/* ===============================
   SEMANA
=============================== */

App.getInicioSemana = function (data) {
    if (data instanceof Date) {
        return new Date(
            data.getFullYear(),
            data.getMonth(),
            data.getDate()
        );
    }

    if (typeof data === "string") {
        const [ano, mes, dia] = data.split("-").map(Number);
        return new Date(ano, mes - 1, dia);
    }

    console.error("Data inválida recebida em getInicioSemana:", data);
    return null;
};


App.renderSemana = function () {
    const container = document.getElementById("diasSemana");
    container.innerHTML = "";

    let inicio;

    if (App.state.dataInicioSemana instanceof Date) {
        inicio = new Date(
            App.state.dataInicioSemana.getFullYear(),
            App.state.dataInicioSemana.getMonth(),
            App.state.dataInicioSemana.getDate()
        );
    } else if (typeof App.state.dataInicioSemana === "string") {
        const [ano, mes, dia] = App.state.dataInicioSemana.split("-").map(Number);
        inicio = new Date(ano, mes - 1, dia);
    } else {
        console.error("dataInicioSemana inválida:", App.state.dataInicioSemana);
        return;
    }

    for (let i = 0; i < 7; i++) {
        const dia = new Date(inicio);
        dia.setDate(inicio.getDate() + i);

        // 🔥 Correção sem bug de timezone
        const dataISO =
            dia.getFullYear() + "-" +
            String(dia.getMonth() + 1).padStart(2, "0") + "-" +
            String(dia.getDate()).padStart(2, "0");

        // Eventos do dia filtrados pela data e categoria ativa
        const eventosDoDia = App.state.eventos.filter(ev => {
            const mesmoDia = ev.data_evento?.split(" ")[0] === dataISO;
            const categoriaAtiva = !App.state.categoriasAtivas || App.state.categoriasAtivas.length === 0
                ? true
                : App.state.categoriasAtivas.includes(ev.categoria_grande_id);
            return mesmoDia && categoriaAtiva;
        });

        const div = document.createElement("div");
        div.className = "dia-card";
        div.dataset.data = dataISO;

        // Renderiza os 2 primeiros eventos como badge e quantidade total
        const eventosHTML = eventosDoDia.slice(0, 2)
            .map(ev => `<span class="badge bg-secondary me-1">${ev.titulo}</span>`)
            .join("<br>");

        const maisEventos = eventosDoDia.length > 2
            ? `<br><span>+${eventosDoDia.length - 2} eventos</span>`
            : eventosDoDia.length > 0
                ? `<br><span>${eventosDoDia.length} evento${eventosDoDia.length > 1 ? 's' : ''}</span>`
                : `<br><span>0 eventos</span>`;

        div.innerHTML = `
            <strong>${dia.toLocaleDateString('pt-BR', { weekday: 'short' })}</strong><br>
            ${dia.getDate()}/${dia.getMonth() + 1}
            <div class="dia-mini-eventos mt-2">
                ${eventosHTML}
                ${maisEventos}
            </div>
        `;

        div.addEventListener("click", () => {
            App.state.diaSelecionado = dataISO;
            App.state.filtroEventoId = null;

            document.querySelectorAll(".dia-card").forEach(d => d.classList.remove("active"));
            div.classList.add("active");

            App.renderEventosDia();
            App.filtrarMapaPorDia(dataISO);
            App.ajustarBuscaChat();
        });

        container.appendChild(div);
    }

    const fim = new Date(inicio);
    fim.setDate(inicio.getDate() + 6);

    document.getElementById("tituloSemana").innerText =
        `${inicio.toLocaleDateString()} - ${fim.toLocaleDateString('pt-BR')}`;

    App.definirDiaPadrao();

    // 🔥 CSS necessário para o carrossel
    container.style.display = "flex";
    container.style.overflowX = "auto";
    container.style.gap = "10px";
    container.style.scrollSnapType = "x mandatory";

    // Cada card com snap e largura mínima (aprox. 1/3 da tela)
    document.querySelectorAll(".dia-card").forEach(card => {
        card.style.minWidth = "calc(20% - 10px)";
        card.style.flex = "0 0 auto";
        card.style.scrollSnapAlign = "start";
    });
};


App.definirDiaPadrao = function () {

    const hoje = new Date();
    const hojeISO =
        hoje.getFullYear() + "-" +
        String(hoje.getMonth() + 1).padStart(2, "0") + "-" +
        String(hoje.getDate()).padStart(2, "0");

    let dataPadrao = null;

    // se hoje tem evento
    if (App.state.eventos.some(ev => ev.data_evento === hojeISO)) {
        dataPadrao = hojeISO;
    } else {

        // pega primeiro evento da semana atual
        const datasSemana = [...document.querySelectorAll(".dia-card")]
            .map(d => d.dataset.data);

        dataPadrao = datasSemana.find(data =>
            App.state.eventos.some(ev => ev.data_evento === data)
        );
    }

    if (!dataPadrao) {
        // se não tem nenhum evento na semana, seleciona o primeiro dia
        dataPadrao = document.querySelector(".dia-card")?.dataset.data;
    }

    if (!dataPadrao) return;

    App.state.diaSelecionado = dataPadrao;

    const el = document.querySelector(`[data-data="${dataPadrao}"]`);
    if (el) el.classList.add("active");

    App.renderEventosDia();
    App.filtrarMapaPorDia(dataPadrao);
};



/* ===============================
   EVENTOS DO DIA
=============================== */

App.renderEventosDia = function () {

    const container = document.getElementById("listaEventosDia");
    container.innerHTML = "";

    if (!App.state.diaSelecionado) return;

    const eventos = App.state.eventos.filter(ev =>
        (ev.data_evento.split(" ")[0] === App.state.diaSelecionado) && ((App.state.categoriasAtivas.length == 0) || App.state.categoriasAtivas.includes(ev.categoria_grande))
    );


    eventos.forEach(ev => {

        const div = document.createElement("div");
        div.className = "evento-item";
        const dataEvento = new Date(ev.data_evento); // cria Date a partir da string
        const dia = String(dataEvento.getDate()).padStart(2, '0');
        const mes = String(dataEvento.getMonth() + 1).padStart(2, '0'); // meses começam do 0
        const ano = dataEvento.getFullYear();
        const hora = String(dataEvento.getHours()).padStart(2, '0');
        const minuto = String(dataEvento.getMinutes()).padStart(2, '0');

        const dataFormatada = `${dia}/${mes}/${ano} ${hora}:${minuto}`;

        div.innerHTML = `
            <strong>${ev.titulo} - ${dataFormatada}</strong><br>
            <img title="${ev.categoria_grande}" src="${App.getPinUrl(ev.cor_mapa)}">${ev.endereco}
            <br>
            <button class="btn btn-sm btn-outline-primary mt-1">
                Ver no mapa
            </button>
        `;

        div.addEventListener("click", () => {
            App.mostrarDetalhesEvento(ev, div);
            e.stopPropagation();
        });

        div.querySelector("button")
            .addEventListener("click", () => {
                App.filtrarMapaPorEvento(ev);
            });



        container.appendChild(div);
    });
};

/* ===============================
   INTEGRAÇÃO COM MAPA
=============================== */

App.filtrarMapaPorDia = function (dataISO) {

    App.state.dataFiltroFormatada = App.formatarDataBR(dataISO);
    App.atualizarMarkers();
};

App.filtrarMapaPorEvento = function (evento) {

    App.state.dataFiltroFormatada = App.formatarDataBR(evento.data_evento);

    if (!App.state.filtroEventoId) {
        App.state.filtroEventoId = evento.id;
    } else {
        App.state.filtroEventoId = null
    }

    App.atualizarMarkers();
};

/* ===============================
   MODAL
=============================== */

App.mostrarDetalhesEvento = async function (ev, itemEvento) {

    const navegacaoAberta = document.getElementById('evento-card-' + ev.id)
    if (navegacaoAberta) {
        navegacaoAberta.remove();
        return;
    }

    // Remove detalhe temporário existente
    const detalheExistente = document.querySelector(".evento-detalhe-temporaria");
    if (detalheExistente) detalheExistente.remove();

    // Busca a imagem do evento
    let img;
    try {
        const res = await fetch(`${BASE_URL}/../api/evento_flyer.php?id=${ev.id}`);
        const data = await res.json();
        img = data.imagem || imgDefault;
    } catch (err) {
        console.error("Erro ao buscar imagem do evento", err);
        img = imgDefault;
    }

    // Cria a div de detalhe
    const div = document.createElement("div");
    div.setAttribute('id', 'evento-card-' + ev.id);
    div.className = "evento-detalhe-temporaria card p-3 shadow-sm mb-3";

    div.innerHTML = `
        ${img ? `
        <div class="evento-imagem-wrapper position-relative">
            <img style="max-width:250px" src="${img}" class="card-img-top rounded-top" alt="${ev.titulo}">
            <div class="overlay-titulo position-absolute bottom-0 start-0 w-100 p-2 bg-dark bg-opacity-50 text-white">
                <h4 class="mb-0">${ev.titulo}</h4>
            </div>
        </div>` : `
        <div class="p-3">
            <h4>${ev.titulo}</h4>
        </div>`}

        <div class="card-body">
            ${ev.descricao ? `<p class="card-text">${ev.descricao}</p>` : ""}
            <ul class="list-unstyled mb-3">
                ${ev.local_nome ? `<li><strong>Local:</strong> ${ev.local_nome}</li>` : ""}
                ${ev.data_evento ? `<li><strong>Data:</strong> ${ev.data_evento.split(' ')[0]}</li>` : ""}
            </ul>

            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary flex-grow-1" id="btnVoltarDetalhe">← Voltar</button>
                <button class="btn btn-primary flex-grow-1" id="btnVerMapa">Ver no mapa</button>
            </div>
        </div>
    `;

    // Insere logo abaixo do item clicado
    itemEvento.insertAdjacentElement("afterend", div);

    // Botão voltar — remove só esta div temporária
    div.querySelector("#btnVoltarDetalhe").addEventListener("click", () => {
        div.remove();
    });

    // Botão ver no mapa
    div.querySelector("#btnVerMapa").addEventListener("click", () => {
        App.filtrarMapaPorEvento(ev);
        div.scrollIntoView({ behavior: "smooth" });
    });
};

