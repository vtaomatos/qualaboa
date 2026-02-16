App.carregarEventosAPI = async function (data) {

    try {

        const response = await fetch(
            `${BASE_URL}/../api/events.php?data=${data}`
        );

        const dataJson = await response.json();

        App.state.eventos = dataJson;

        App.initAgenda();
        App.atualizarMarkers();

    } catch (err) {
        console.error("Erro ao carregar eventos:", err);
    }
};


