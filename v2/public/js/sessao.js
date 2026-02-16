/* ===============================
   LOG DE SESSÃO E ACESSO (PROGRESSIVO)
=============================== */

let sessaoConfirmada = false;
let acessoRegistrado = false;
let tempoSessao = 0; // em segundos
let ultimoHeartbeat = Date.now();

let localizacaoUsuario = App.state?.fallbackLocation ?? {
    lat: -23.9608,
    lng: -46.3331
};

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

    navigator.sendBeacon(`${BASE_URL}/../api/log_sessao.php`, JSON.stringify({
        evento,
        sessao_id: App.state.sessao_id,
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

    fetch(`${BASE_URL}/../api/log_access.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tipo: 'user_interaction',
            page: window.location.pathname + location.search,
            sessao_id: App.state.sessao_id,
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
