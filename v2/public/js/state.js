window.App = {
    state: {
        sessao_id: sessionStorage.getItem("sessao_id") || crypto.randomUUID(),
        dataFiltroFormatada: window.APP_CONFIG?.dataFiltroFormatada ?? null,
        eventos: [],
        ordemPrioridade: [],
        map: null,
        markers: [],
        infoWindow: null,
        categoriasCores: window.APP_CONFIG?.categorias ?? {},
        categoriasAtivas: [],
        filtradoPorEvento: false,
        fallbackLocation: {
            lat: -23.9608,
            lng: -46.3331
        }
    }
};

sessionStorage.setItem("sessao_id", App.state.sessao_id);

App.agruparEventosPorLocal = function (eventos) {

    if (!Array.isArray(eventos)) return {};

    const agrupados = {};

    eventos.forEach(ev => {

        if (!ev || !ev.latitude || !ev.longitude) return;

        const key = `${parseFloat(ev.latitude).toFixed(5)}_${parseFloat(ev.longitude).toFixed(5)}`;

        if (!agrupados[key]) agrupados[key] = [];

        agrupados[key].push(ev);
    });

    return agrupados;
};

App.mostrarLocalizacaoComAnimacao = function (map) {
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



