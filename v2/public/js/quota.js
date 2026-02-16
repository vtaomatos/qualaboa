/* =================== 
      CONTROLE DE QUOTA 
    =================== */

async function carregarQuota() {
    if (!App.state.sessao_id) return null;

    try {
        const res = await fetch(`${BASE_URL}/../api/quota.php`, {
            headers: {
                'X-Session-Id': App.state.sessao_id
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