async function enviarPergunta() {

    const quota = await carregarQuota();

    if (!quota || quota.disponiveis <= 0) {
        bloquearEnvio();
        return;
    }

    bloquearEnvio();

    const pergunta = document.getElementById("userInput").value;
    const data = App.state.diaSelecionado;

    if (!pergunta.trim()) {
        liberarEnvio();
        return;
    }

    document.getElementById("explicacaoIA").textContent = "⏳ Pensando...";

    try {
        const eventos_id = App.state.eventos
            .filter(ev => {
                // Filtra pelo dia
                const eventoData = ev.data_evento.split(' ')[0]; // pega só a data
                const mesmoDia = eventoData === App.state.diaSelecionado;


                // Filtra pela categoria
                const categoriaAtiva = (App.state.categoriasAtivas.length == 0) || App.state.categoriasAtivas.includes(ev.categoria_grande);

                return mesmoDia && categoriaAtiva;
            })
            .map(ev => ev.id);


        const response = await fetch(`${BASE_URL}/../api/chat.php?`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Session-Id": App.state.sessao_id
            },
            body: JSON.stringify({
                pergunta,
                data,
                eventos_id: eventos_id
            })
        });

        const dataResposta = await response.json();

        if (dataResposta.erro) {
            document.getElementById("explicacaoIA").innerHTML =
                `<span style="color:red;">
          ⚠️ ${dataResposta.erro}
          ${dataResposta.codigo ? `<br>Código: ${dataResposta.codigo}` : ''}
        </span>`;
            document.getElementById("recomendacoesChat").innerHTML = "";
            liberarEnvio();
            return;
        }

        const resposta = parseRespostaIA(dataResposta.resposta);

        // 🔥 Atualiza prioridade corretamente
        ordemPrioridade = resposta.ordem || [];

        // 🔥 Se você usa App.state também, mantenha sincronizado
        if (window.App && App.state) {
            App.state.ordemPrioridade = ordemPrioridade;
            document.dispatchEvent(new Event("eventosAtualizados"));
        }

        let explicacaoFormatada =
            normalizarDataHora(resposta.explicacao || "");

        explicacaoFormatada =
            melhorarHTMLIA(explicacaoFormatada);

        document.getElementById("explicacaoIA").innerHTML = explicacaoFormatada;
        document.getElementById("recomendacoesChat").innerHTML = "";

        liberarEnvio();
        carregarQuota();

        // Mantém seu comportamento original
        App.initMap()

        document.scrollingElement.scrollTo(0, 999999);

    } catch (error) {

        console.error("Erro no chat:", error);

        document.getElementById("explicacaoIA").innerHTML =
            `<span style="color:red;">⚠️ Erro inesperado.</span>`;

        liberarEnvio();
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

App.ajustarBuscaChat = function () {
    let spanDiaChat = document.getElementById('dia-chat');
    const apenasDataSelecionada = App.formatarDataBR(App.state.diaSelecionado).split(' ')[0];
    spanDiaChat.innerHTML = "Dia: " + apenasDataSelecionada;

    const regiao = document.getElementById('regiao-3');
    if (!regiao) return;

    const toggleBtn = regiao.querySelector('.toggle-btn');
    if (!toggleBtn) return;


    if (toggleBtn.textContent.includes("Ocultar")) {
        return;
    }

    toggleBtn.textContent =
        // `↑ Busca com IA no dia ${apenasDataSelecionada}`;
        `↑ Busca com IA no dia`;

}