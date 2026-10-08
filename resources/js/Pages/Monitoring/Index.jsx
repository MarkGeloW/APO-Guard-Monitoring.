import { useEffect, useRef, useState } from 'react';
import AdminLayout from '@/Layouts/AdminLayout';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const statusColors = {
    on_duty: '#10b981',
    late: '#f59e0b',
    absent: '#ef4444',
    off_duty: '#94a3b8',
};

function createGuardIcon(status) {
    const color = statusColors[status] || statusColors.off_duty;
    return L.divIcon({
        className: 'apo-map-marker',
        html: `<div style="width:18px;height:18px;border-radius:9999px;background:${color};border:3px solid #fff;box-shadow:0 2px 8px rgba(15,23,42,.28);position:relative">
            <span style="position:absolute;inset:-8px;border-radius:9999px;border:2px solid ${color};opacity:.35"></span>
        </div>`,
        iconSize: [18, 18],
        iconAnchor: [9, 9],
        popupAnchor: [0, -12],
    });
}

function formatCoord(lat, lng) {
    const nLat = Number(lat);
    const nLng = Number(lng);
    if (Number.isNaN(nLat) || Number.isNaN(nLng)) return '7.01°N, 125.09°E';
    return `${nLat.toFixed(3)}°N, ${nLng.toFixed(3)}°E`;
}

function statusClass(status) {
    return (
        {
            on_duty: 'text-emerald-600',
            late: 'text-amber-600',
            absent: 'text-red-500',
            off_duty: 'text-slate-500',
        }[status] || 'text-slate-500'
    );
}

function popupHtml(guard) {
    const color = statusColors[guard.markerStatus] || statusColors.off_duty;
    const time = guard.time_in ? ` ${guard.time_in}` : '';
    return `
        <div style="min-width:160px">
            <div style="font-weight:700;color:#0f172a;font-size:13px">${guard.name}</div>
            <div style="color:#64748b;font-size:12px;margin-top:2px">${guard.location || ''}</div>
            <div style="display:flex;align-items:center;gap:6px;margin-top:8px;font-size:12px;font-weight:600;color:${color}">
                <span style="width:8px;height:8px;border-radius:9999px;background:${color}"></span>
                ${guard.status_label || 'Off Duty'}${time}
            </div>
            <div style="margin-top:6px;font-size:12px;color:#475569">Duty check: ${guard.signal_label || '—'}</div>
        </div>
    `;
}

export default function Index({ mapData, guards = [] }) {
    const [selectedId, setSelectedId] = useState(guards[0]?.id ?? null);
    const containerRef = useRef(null);
    const mapRef = useRef(null);
    const markersRef = useRef({});

    const selected = guards.find((g) => g.id === selectedId) || guards[0] || null;

    const focusGuard = (guard) => {
        setSelectedId(guard.id);
        const marker = markersRef.current[guard.id];
        if (marker && mapRef.current) {
            mapRef.current.setView(marker.getLatLng(), 15, { animate: true });
            marker.openPopup();
        }
    };

    useEffect(() => {
        const el = containerRef.current;
        if (!el) return;

        if (mapRef.current) {
            mapRef.current.remove();
            mapRef.current = null;
        }

        el.innerHTML = '';

        const map = L.map(el, {
            zoomControl: false,
            attributionControl: true,
        }).setView([7.0083, 125.0928], 13.5);

        mapRef.current = map;
        L.control.zoom({ position: 'topright' }).addTo(map);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19,
        }).addTo(map);

        const bounds = [];
        const markers = {};

        (mapData?.locations || []).forEach((loc) => {
            const lat = Number(loc.lat);
            const lng = Number(loc.lng);
            if (Number.isNaN(lat) || Number.isNaN(lng)) return;

            L.circle([lat, lng], {
                radius: Math.max(Number(loc.radius) || 150, 120),
                color: loc.borderColor || '#93c5fd',
                fillColor: loc.fillColor || '#dbeafe',
                fillOpacity: 0.28,
                weight: 2,
            }).addTo(map);

            L.marker([lat, lng], { opacity: 0, interactive: false })
                .bindTooltip(loc.zone || loc.name, {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -8],
                    className: 'apo-zone-tag',
                })
                .addTo(map);

            bounds.push([lat, lng]);
        });

        (guards || []).forEach((guard, index) => {
            let lat = Number(guard.lat);
            let lng = Number(guard.lng);
            if (Number.isNaN(lat) || Number.isNaN(lng)) return;

            lat += index * 0.00018;
            lng += index * 0.00018;

            const marker = L.marker([lat, lng], {
                icon: createGuardIcon(guard.markerStatus),
            }).addTo(map);

            marker.bindPopup(popupHtml(guard), { closeButton: false, offset: [0, -4] });
            marker.on('click', () => setSelectedId(guard.id));
            markers[guard.id] = marker;
            bounds.push([lat, lng]);
        });

        markersRef.current = markers;

        if (bounds.length) {
            map.fitBounds(bounds, { padding: [48, 48], maxZoom: 15 });
        }

        const legend = L.control({ position: 'bottomright' });
        legend.onAdd = () => {
            const div = L.DomUtil.create('div');
            div.style.cssText =
                'background:rgba(255,255,255,.96);border:1px solid #e2e8f0;border-radius:12px;padding:10px 14px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#64748b;box-shadow:0 8px 24px rgba(15,23,42,.08)';
            div.innerHTML = `
                <div style="margin-bottom:4px;color:#94a3b8">Status</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:6px"><span style="width:10px;height:10px;border-radius:9999px;background:#10b981"></span> On Duty</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:6px"><span style="width:10px;height:10px;border-radius:9999px;background:#f59e0b"></span> Late</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:6px"><span style="width:10px;height:10px;border-radius:9999px;background:#ef4444"></span> Absent</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:6px"><span style="width:10px;height:10px;border-radius:9999px;background:#94a3b8"></span> Off Duty</div>
            `;
            return div;
        };
        legend.addTo(map);

        const coords = L.control({ position: 'bottomleft' });
        coords.onAdd = () => {
            const div = L.DomUtil.create('div');
            div.style.cssText =
                'background:transparent;border:none;padding:4px 8px;font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#64748b;text-shadow:0 1px 0 #fff';
            div.innerHTML = '7.01°N 125.09°E · Kidapawan City';
            return div;
        };
        coords.addTo(map);

        const resize = () => map.invalidateSize();
        const t1 = window.setTimeout(resize, 80);
        const t2 = window.setTimeout(resize, 350);
        window.addEventListener('resize', resize);

        // Open popup for initial selection
        if (selectedId && markers[selectedId]) {
            window.setTimeout(() => markers[selectedId]?.openPopup(), 400);
        }

        return () => {
            window.clearTimeout(t1);
            window.clearTimeout(t2);
            window.removeEventListener('resize', resize);
            map.remove();
            mapRef.current = null;
            markersRef.current = {};
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [mapData, guards]);

    useEffect(() => {
        const marker = markersRef.current[selectedId];
        if (marker) {
            marker.openPopup();
        }
    }, [selectedId]);

    const breadcrumb = (
        <>
            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z" />
                <circle cx="12" cy="11" r="2.5" />
            </svg>
            <span>APO Security Agency</span>
            <span>›</span>
            <span className="text-slate-600">Live Deployment Map</span>
        </>
    );

    return (
        <AdminLayout breadcrumb={breadcrumb}>
            <style>{`
                .apo-map-marker { background: transparent !important; border: none !important; }
                .apo-zone-tag {
                    background: #fff !important;
                    border: 1px solid #e2e8f0 !important;
                    border-radius: 8px !important;
                    box-shadow: 0 4px 12px rgba(15,23,42,.08) !important;
                    color: #334155 !important;
                    font-size: 11px !important;
                    font-weight: 800 !important;
                    letter-spacing: .08em !important;
                    padding: 4px 8px !important;
                    text-transform: uppercase !important;
                }
                .apo-zone-tag::before { display: none !important; }
                .leaflet-container { width: 100%; height: 100%; font-family: inherit; border-radius: 0; z-index: 0 !important; }
                .leaflet-popup-content-wrapper {
                    border-radius: 12px !important;
                    box-shadow: 0 10px 30px rgba(15,23,42,.12) !important;
                    border: 1px solid #e2e8f0;
                }
                .leaflet-popup-content { margin: 12px 14px !important; }
                .leaflet-popup-tip { box-shadow: none !important; }
            `}</style>

            <div className="mb-6">
                <h2 className="text-2xl font-extrabold uppercase tracking-wide text-slate-900">
                    Live Deployment Map
                </h2>
                <p className="mt-1 text-sm text-slate-500">
                    Real-time GIS visualization — click a marker to inspect a guard
                </p>
            </div>

            <div className="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_300px]">
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div ref={containerRef} className="h-[560px] w-full bg-[#e8eef5]" />
                </div>

                <div className="flex flex-col gap-5">
                    <div className="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
                            <svg className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                <path d="M16 11a4 4 0 1 0-8 0m-2 10a6 6 0 0 1 12 0" />
                            </svg>
                            <h3 className="text-xs font-extrabold uppercase tracking-[.18em] text-slate-700">Guards</h3>
                        </div>
                        <div className="max-h-[280px] overflow-y-auto xl:max-h-[320px]">
                            {!guards.length ? (
                                <p className="px-5 py-8 text-center text-sm text-slate-500">No guards deployed today.</p>
                            ) : (
                                guards.map((guard) => {
                                    const active = selected?.id === guard.id;
                                    return (
                                        <button
                                            key={guard.id}
                                            type="button"
                                            onClick={() => focusGuard(guard)}
                                            className={`flex w-full items-center gap-3 border-b border-slate-100 px-5 py-4 text-left transition ${
                                                active ? 'bg-blue-50' : 'bg-white hover:bg-slate-50'
                                            }`}
                                        >
                                            <span
                                                className="h-2.5 w-2.5 shrink-0 rounded-full"
                                                style={{
                                                    background:
                                                        statusColors[guard.markerStatus] || statusColors.off_duty,
                                                }}
                                            />
                                            <span className="min-w-0 flex-1">
                                                <span className="block truncate text-sm font-bold text-slate-900">
                                                    {guard.name}
                                                </span>
                                                <span className="block text-xs text-slate-500">
                                                    {guard.zone}
                                                    {guard.signal_label ? ` · ${guard.signal_label}` : ''}
                                                </span>
                                            </span>
                                            <svg
                                                className="h-4 w-4 shrink-0 text-slate-300"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="1.8"
                                                viewBox="0 0 24 24"
                                            >
                                                <path d="m9 6 6 6-6 6" />
                                            </svg>
                                        </button>
                                    );
                                })
                            )}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div className="mb-4 flex items-center gap-2">
                            <svg className="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                <circle cx="12" cy="8" r="3.5" />
                                <path d="M5 20a7 7 0 0 1 14 0" />
                            </svg>
                            <h3 className="text-xs font-extrabold uppercase tracking-[.18em] text-slate-700">
                                Guard Detail
                            </h3>
                        </div>

                        {!selected ? (
                            <p className="text-sm text-slate-500">Select a guard to view details.</p>
                        ) : (
                            <>
                                <p className="text-base font-bold text-slate-900">{selected.name}</p>
                                <dl className="mt-4 space-y-2.5 text-sm">
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-slate-400">ID</dt>
                                        <dd className="font-semibold text-slate-800">
                                            {selected.guard_code || selected.badge || '—'}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-slate-400">Status</dt>
                                        <dd className={`font-semibold lowercase ${statusClass(selected.markerStatus)}`}>
                                            {(selected.status_label || 'off duty').toLowerCase().replace(' ', '-')}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-slate-400">Post</dt>
                                        <dd className="max-w-[180px] text-right font-semibold text-slate-800">
                                            {selected.location}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-slate-400">Zone</dt>
                                        <dd className="font-semibold text-slate-800">{selected.zone}</dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-slate-400">Time In</dt>
                                        <dd className="font-semibold text-slate-800">{selected.time_in || '—'}</dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-slate-400">Duty check</dt>
                                        <dd className="inline-flex items-center gap-1.5 font-semibold text-slate-800">
                                            <span
                                                className={`h-2 w-2 rounded-full ${
                                                    selected.signal_tone === 'green'
                                                        ? 'bg-emerald-500'
                                                        : selected.signal_tone === 'amber'
                                                          ? 'bg-amber-500'
                                                          : selected.signal_tone === 'red'
                                                            ? 'bg-red-500'
                                                            : 'bg-slate-300'
                                                }`}
                                            />
                                            {selected.signal_label || '—'}
                                        </dd>
                                    </div>
                                    <div className="flex justify-between gap-3">
                                        <dt className="text-slate-400">Shift</dt>
                                        <dd className="font-semibold text-slate-800">
                                            {selected.shift_type || 'Day'}
                                        </dd>
                                    </div>
                                </dl>
                                <div className="mt-4 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-xs font-semibold text-slate-500">
                                    <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" strokeWidth="1.8" viewBox="0 0 24 24">
                                        <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z" />
                                        <circle cx="12" cy="11" r="2.5" />
                                    </svg>
                                    {formatCoord(selected.lat, selected.lng)}
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
