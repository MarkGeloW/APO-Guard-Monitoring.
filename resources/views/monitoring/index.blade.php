<x-admin-layout>
    <x-slot name="header">Live Deployment Map</x-slot>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Live Deployment Map</span>
    </x-slot>

    <div
        x-data="{
            selectedGuard: null,
            guards: @js($guards),
            selectGuard(guard) {
                this.selectedGuard = guard;
                window.focusGuardMarker?.(guard.id);
            }
        }"
        @guard-selected.window="selectGuard($event.detail)"
    >
        <div class="mb-6">
            <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">Live Deployment Map</h2>
            <p class="mt-1 text-sm text-slate-500">Real-time GIS visualization — click a marker to inspect a guard.</p>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_300px]">
            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div id="monitoring-map" class="h-[620px] bg-slate-100"></div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
                    <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0"/></svg>
                    <h3 class="text-sm font-extrabold uppercase tracking-[.16em] text-slate-700">Guards</h3>
                </div>

                <div id="guard-list" class="max-h-[560px] divide-y divide-slate-100 overflow-y-auto">
                    @foreach($guards as $guard)
                        @php
                            $dotColor = match($guard['markerStatus']) {
                                'on_duty' => 'bg-emerald-500',
                                'late' => 'bg-amber-500',
                                'absent' => 'bg-red-500',
                                default => 'bg-slate-400',
                            };
                        @endphp
                        <button
                            type="button"
                            @click="selectGuard(@js($guard))"
                            class="flex w-full items-center gap-3 px-5 py-4 text-left transition hover:bg-slate-50"
                            :class="selectedGuard && selectedGuard.id === {{ $guard['id'] }} ? 'bg-blue-50' : ''"
                        >
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $dotColor }}"></span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-semibold text-slate-900">{{ $guard['name'] }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $guard['zone'] }}</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg>
                        </button>
                    @endforeach
                </div>

                <div x-show="selectedGuard" x-cloak class="border-t border-slate-100 bg-slate-50 px-5 py-4">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Selected Guard</p>
                    <p class="mt-1 font-bold text-slate-900" x-text="selectedGuard?.name"></p>
                    <p class="text-sm text-slate-500" x-text="selectedGuard?.location"></p>
                    <p class="mt-2 text-xs text-slate-500">
                        <span x-text="selectedGuard?.zone"></span>
                        <span x-show="selectedGuard?.time_in"> · Checked in <span x-text="selectedGuard?.time_in"></span></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <button type="button" class="fixed bottom-6 right-6 z-50 flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-lg font-bold text-slate-500 shadow-lg hover:text-slate-800" aria-label="Help">?</button>

    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #monitoring-map .leaflet-control-attribution { font-size: 10px; }
        .guard-marker-pulse {
            width: 18px;
            height: 18px;
            border-radius: 9999px;
            border: 3px solid #fff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.25);
            position: relative;
        }
        .guard-marker-pulse::after {
            content: '';
            position: absolute;
            inset: -8px;
            border-radius: 9999px;
            border: 2px solid currentColor;
            opacity: 0.35;
        }
        .map-legend {
            background: rgba(255,255,255,0.95);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
            box-shadow: 0 8px 24px rgba(15,23,42,.08);
        }
        .map-legend-row { display:flex; align-items:center; gap:8px; margin-top:6px; }
        .map-legend-dot { width:10px; height:10px; border-radius:9999px; }
        .zone-label {
            background: transparent;
            border: none;
            box-shadow: none;
            font-size: 13px;
            font-weight: 800;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .12em;
        }
        .zone-label::before { display: none; }
    </style>
    @endpush

    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const mapData = @json($mapData);
        const markerColors = {
            on_duty: '#10b981',
            late: '#f59e0b',
            absent: '#ef4444',
            off_duty: '#94a3b8',
        };

        const map = L.map('monitoring-map', { zoomControl: false }).setView([7.0083, 125.0928], 14);
        L.control.zoom({ position: 'topright' }).addTo(map);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19
        }).addTo(map);

        const locationLayer = L.layerGroup().addTo(map);
        const guardLayer = L.layerGroup().addTo(map);
        const markerIndex = {};

        function createGuardIcon(status) {
            const color = markerColors[status] || markerColors.off_duty;
            return L.divIcon({
                className: '',
                html: `<div class="guard-marker-pulse" style="background:${color}; color:${color};"></div>`,
                iconSize: [18, 18],
                iconAnchor: [9, 9],
            });
        }

        function renderMap(data) {
            locationLayer.clearLayers();
            guardLayer.clearLayers();
            Object.keys(markerIndex).forEach(key => delete markerIndex[key]);

            data.locations.forEach(loc => {
                L.circle([loc.lat, loc.lng], {
                    radius: Math.max(loc.radius, 120),
                    color: loc.borderColor,
                    fillColor: loc.fillColor,
                    fillOpacity: 0.45,
                    weight: 2,
                }).addTo(locationLayer).bindPopup(`<strong>${loc.zone}</strong><br>${loc.name}`);

                L.marker([loc.lat, loc.lng], {
                    opacity: 0,
                    interactive: false,
                }).bindTooltip(loc.zone, {
                    permanent: true,
                    direction: 'center',
                    className: 'zone-label',
                }).addTo(locationLayer);
            });

            data.guards.forEach(guard => {
                const marker = L.marker([guard.lat, guard.lng], {
                    icon: createGuardIcon(guard.markerStatus),
                }).addTo(guardLayer);

                marker.bindPopup(`
                    <strong>${guard.name}</strong><br>
                    ${guard.location}<br>
                    ${guard.zone}<br>
                    Status: ${guard.markerStatus.replace('_', ' ')}
                `);

                marker.on('click', () => {
                    window.dispatchEvent(new CustomEvent('guard-selected', { detail: guard }));
                });

                markerIndex[guard.id] = marker;
            });
        }

        window.focusGuardMarker = (id) => {
            const marker = markerIndex[id];
            if (!marker) return;
            map.setView(marker.getLatLng(), 15, { animate: true });
            marker.openPopup();
        };

        renderMap(mapData);

        const legend = L.control({ position: 'bottomright' });
        legend.onAdd = function () {
            const div = L.DomUtil.create('div', 'map-legend');
            div.innerHTML = `
                <div>Status</div>
                <div class="map-legend-row"><span class="map-legend-dot" style="background:#10b981"></span> On Duty</div>
                <div class="map-legend-row"><span class="map-legend-dot" style="background:#f59e0b"></span> Late</div>
                <div class="map-legend-row"><span class="map-legend-dot" style="background:#ef4444"></span> Absent</div>
            `;
            return div;
        };
        legend.addTo(map);

        async function refreshLive() {
            try {
                const res = await fetch('{{ route('monitoring.live') }}');
                const data = await res.json();
                renderMap({ locations: mapData.locations, guards: data.guards });
            } catch (e) {
                console.error('Live map refresh failed', e);
            }
        }

        setInterval(refreshLive, 15000);
    </script>
    @endpush
</x-admin-layout>
