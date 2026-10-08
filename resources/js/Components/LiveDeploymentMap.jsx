import { useEffect, useRef } from 'react';
import { Link } from '@inertiajs/react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const markerColors = {
    on_duty: '#10b981',
    late: '#f59e0b',
    absent: '#ef4444',
    off_duty: '#94a3b8',
};

function createGuardIcon(status) {
    const color = markerColors[status] || markerColors.off_duty;
    return L.divIcon({
        className: 'apo-dash-marker',
        html: `<div style="width:16px;height:16px;border-radius:9999px;background:${color};border:3px solid #fff;box-shadow:0 2px 8px rgba(15,23,42,.28);position:relative">
            <span style="position:absolute;inset:-7px;border-radius:9999px;border:2px solid ${color};opacity:.35"></span>
        </div>`,
        iconSize: [16, 16],
        iconAnchor: [8, 8],
    });
}

export default function LiveDeploymentMap({ mapData }) {
    const containerRef = useRef(null);
    const mapRef = useRef(null);

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
            attributionControl: false,
        }).setView([7.0083, 125.0928], 13.5);

        mapRef.current = map;
        L.control.zoom({ position: 'topright' }).addTo(map);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        const bounds = [];

        (mapData?.locations || []).forEach((loc) => {
            const lat = Number(loc.lat);
            const lng = Number(loc.lng);
            if (Number.isNaN(lat) || Number.isNaN(lng)) return;

            L.circle([lat, lng], {
                radius: Math.max(Number(loc.radius) || 150, 120),
                color: loc.borderColor || '#93c5fd',
                fillColor: loc.fillColor || '#dbeafe',
                fillOpacity: 0.35,
                weight: 2,
            }).addTo(map);

            L.marker([lat, lng], { opacity: 0, interactive: false })
                .bindTooltip(loc.zone || loc.name, {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -6],
                    className: 'apo-dash-zone-tag',
                })
                .addTo(map);

            bounds.push([lat, lng]);
        });

        (mapData?.guards || []).forEach((guard, index) => {
            let lat = Number(guard.lat);
            let lng = Number(guard.lng);
            if (Number.isNaN(lat) || Number.isNaN(lng)) return;

            lat += index * 0.00015;
            lng += index * 0.00015;

            L.marker([lat, lng], {
                icon: createGuardIcon(guard.markerStatus),
            })
                .bindPopup(
                    `<strong>${guard.name}</strong><br>${guard.location || ''}<br>${guard.zone || ''}<br>${(guard.markerStatus || '').replace('_', ' ')}`,
                )
                .addTo(map);

            bounds.push([lat, lng]);
        });

        if (bounds.length) {
            map.fitBounds(bounds, { padding: [36, 36], maxZoom: 14 });
        }

        const legend = L.control({ position: 'bottomright' });
        legend.onAdd = () => {
            const div = L.DomUtil.create('div');
            div.style.cssText =
                'background:rgba(255,255,255,.96);border:1px solid #e2e8f0;border-radius:12px;padding:10px 14px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;box-shadow:0 8px 24px rgba(15,23,42,.08)';
            div.innerHTML = `
                <div style="margin-bottom:2px">Status</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:6px"><span style="width:10px;height:10px;border-radius:9999px;background:#10b981"></span> On Duty</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:6px"><span style="width:10px;height:10px;border-radius:9999px;background:#f59e0b"></span> Late</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:6px"><span style="width:10px;height:10px;border-radius:9999px;background:#ef4444"></span> Absent</div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:6px"><span style="width:10px;height:10px;border-radius:9999px;background:#94a3b8"></span> Off Duty</div>
            `;
            return div;
        };
        legend.addTo(map);

        const resize = () => map.invalidateSize();
        const t1 = window.setTimeout(resize, 80);
        const t2 = window.setTimeout(resize, 300);
        window.addEventListener('resize', resize);

        return () => {
            window.clearTimeout(t1);
            window.clearTimeout(t2);
            window.removeEventListener('resize', resize);
            map.remove();
            mapRef.current = null;
        };
    }, [mapData]);

    return (
        <div
            style={{
                overflow: 'hidden',
                borderRadius: 16,
                border: '1px solid #e2e8f0',
                background: '#fff',
                boxShadow: '0 1px 2px rgba(15,23,42,.05)',
                position: 'relative',
                zIndex: 0,
                isolation: 'isolate',
            }}
        >
            <style>{`
                .apo-dash-marker { background: transparent !important; border: none !important; }
                .apo-dash-zone-tag {
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
                .apo-dash-zone-tag::before { display: none !important; }
                .leaflet-container { width: 100%; height: 100%; z-index: 0 !important; }
            `}</style>

            <div
                style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    borderBottom: '1px solid #f1f5f9',
                    padding: '16px 20px',
                }}
            >
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                    <svg width="16" height="16" fill="none" stroke="#64748b" strokeWidth="1.8" viewBox="0 0 24 24">
                        <path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z" />
                        <circle cx="12" cy="11" r="2.5" />
                    </svg>
                    <h2
                        style={{
                            margin: 0,
                            fontSize: 13,
                            fontWeight: 800,
                            letterSpacing: '0.16em',
                            textTransform: 'uppercase',
                            color: '#334155',
                        }}
                    >
                        Live Deployment Map
                    </h2>
                    <span
                        style={{
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: 6,
                            borderRadius: 999,
                            background: '#ecfdf5',
                            padding: '4px 10px',
                            fontSize: 10,
                            fontWeight: 700,
                            letterSpacing: '0.08em',
                            textTransform: 'uppercase',
                            color: '#059669',
                        }}
                    >
                        <span style={{ width: 6, height: 6, borderRadius: 999, background: '#10b981' }} />
                        Live
                    </span>
                </div>
                <Link href="/monitoring" style={{ fontSize: 12, fontWeight: 600, color: '#3b82f6', textDecoration: 'none' }}>
                    Open Full Map
                </Link>
            </div>

            <div style={{ position: 'relative', height: 380, background: '#e8eef5' }}>
                <div ref={containerRef} style={{ position: 'absolute', inset: 0, height: '100%', width: '100%' }} />
            </div>

            <div
                style={{
                    borderTop: '1px solid #f1f5f9',
                    padding: '12px 20px',
                    fontSize: 12,
                    fontWeight: 600,
                    letterSpacing: '0.08em',
                    textTransform: 'uppercase',
                    color: '#94a3b8',
                }}
            >
                7.01°N · 125.09°E · Kidapawan City
            </div>
        </div>
    );
}
