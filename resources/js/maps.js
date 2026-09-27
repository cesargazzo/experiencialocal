// Mapas con Leaflet sobre la base Argenmap del IGN.
// tinkuMap: muestra una zona aproximada (círculo) o un punto.
// tinkuMapPicker: el anfitrión marca el punto de encuentro con un clic o arrastrando.
// Leaflet se descarga solo en las páginas que tienen un mapa.
let L = null;
async function leaflet() {
    if (!L) {
        [L] = await Promise.all([import('leaflet').then((m) => m.default), import('leaflet/dist/leaflet.css')]);
    }
    return L;
}

const brand = { coral: '#FF6335', petroleo: '#006F91' };

function baseMap(el, config, center, zoom) {
    const map = L.map(el, { scrollWheelZoom: false, attributionControl: true }).setView(center, zoom);
    map.attributionControl.setPrefix('<a href="https://leafletjs.com" target="_blank" rel="noopener">Leaflet</a>');
    L.tileLayer(config.tiles, { attribution: config.attribution, minZoom: 3, maxZoom: 18 }).addTo(map);
    return map;
}

function pin(latlng, draggable = false) {
    return L.circleMarker(latlng, { radius: 10, color: '#FFFFFF', weight: 3, fillColor: brand.coral, fillOpacity: 1, interactive: draggable });
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('tinkuMap', ({ config, lat, lng, radius = null, zoom = 14 }) => ({
        async init() {
            await leaflet();
            const map = baseMap(this.$el, config, [lat, lng], zoom);
            if (radius) {
                L.circle([lat, lng], { radius, color: brand.petroleo, weight: 2, fillColor: brand.petroleo, fillOpacity: 0.15 }).addTo(map);
            } else {
                pin([lat, lng]).addTo(map);
            }
        },
    }));

    window.Alpine.data('tinkuMapPicker', ({ config, lat, lng, fallback }) => ({
        map: null,
        marker: null,
        async init() {
            await leaflet();
            const has = lat !== null && lng !== null;
            this.map = baseMap(this.$el, config, has ? [lat, lng] : fallback, has ? 16 : 12);
            this.map.scrollWheelZoom.enable();
            if (has) this.place([lat, lng], false);
            this.map.on('click', (e) => this.place([e.latlng.lat, e.latlng.lng], true));
        },
        place(latlng, notify) {
            if (this.marker) {
                this.marker.setLatLng(latlng);
            } else {
                // Un marcador arrastrable con el estilo de la marca, sin imágenes externas.
                const icon = L.divIcon({ className: 'map-pin', iconSize: [26, 26], iconAnchor: [13, 13] });
                this.marker = L.marker(latlng, { icon, draggable: true }).addTo(this.map);
                this.marker.on('dragend', () => {
                    const p = this.marker.getLatLng();
                    this.$wire.setPoint(p.lat, p.lng);
                });
            }
            if (notify) this.$wire.setPoint(latlng[0], latlng[1]);
        },
        moveTo(detail) {
            this.place([detail.lat, detail.lng], false);
            this.map.setView([detail.lat, detail.lng], detail.zoom || 17);
        },
    }));
});
