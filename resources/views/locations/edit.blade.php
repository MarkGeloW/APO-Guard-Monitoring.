<x-admin-layout>
    <x-slot name="breadcrumb">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M12 21s7-4.5 7-10a7 7 0 1 0-14 0c0 5.5 7 10 7 10Z"/><circle cx="12" cy="11" r="2.5"/></svg>
        <span>APO Security Agency</span>
        <span>›</span>
        <span class="text-slate-600">Edit Location</span>
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-wide text-slate-900">Edit Location</h2>
        <p class="mt-1 text-sm text-slate-500">Update post details, map pin, and GPS geofence radius.</p>
    </div>

    <form method="POST" action="{{ route('locations.update', $location) }}" class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @csrf @method('PUT')
        <div class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <label class="{{ $labelClass }}">Location Name</label>
                <input type="text" name="name" value="{{ old('name', $location->name) }}" required class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Client Name</label>
                <input type="text" name="client_name" value="{{ old('client_name', $location->client_name) }}" class="{{ $inputClass }}">
            </div>
            <div>
                <label class="{{ $labelClass }}">Address</label>
                <textarea name="address" required rows="2" class="{{ $inputClass }}">{{ old('address', $location->address) }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="{{ $labelClass }}">Latitude</label>
                    <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude', $location->latitude) }}" required class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="{{ $labelClass }}">Longitude</label>
                    <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude', $location->longitude) }}" required class="{{ $inputClass }}">
                </div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Geofence Radius (meters)</label>
                <input type="number" name="geofence_radius" id="geofence_radius" value="{{ old('geofence_radius', $location->geofence_radius) }}" min="10" max="5000" required class="{{ $inputClass }}">
                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">
                    How far from this pin a guard may be and still check in successfully.
                </p>
            </div>
            <div>
                <label class="{{ $labelClass }}">Status</label>
                <select name="status" class="{{ $inputClass }}">
                    <option value="active" @selected(old('status', $location->status)==='active')>Active</option>
                    <option value="inactive" @selected(old('status', $location->status)==='inactive')>Inactive</option>
                </select>
            </div>
            <div>
                <label class="{{ $labelClass }}">Description</label>
                <textarea name="description" rows="2" class="{{ $inputClass }}">{{ old('description', $location->description) }}</textarea>
            </div>
            <div class="flex flex-wrap gap-3 pt-1">
                <button type="submit" class="inline-flex items-center rounded-xl bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-300/30 hover:bg-blue-600">Update Location</button>
                <a href="{{ route('locations.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-slate-900">Map Pin</p>
                    <p class="text-xs text-slate-500">Blue circle shows the current geofence area.</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-bold uppercase tracking-wide text-blue-600">GIS</span>
            </div>
            <div id="location-map" class="h-[420px] overflow-hidden rounded-xl ring-1 ring-slate-200"></div>
        </div>
    </form>

    @push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const lat = parseFloat(document.getElementById('latitude').value);
        const lng = parseFloat(document.getElementById('longitude').value);
        const radiusInput = document.getElementById('geofence_radius');
        const map = L.map('location-map').setView([lat, lng], 15);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19
        }).addTo(map);
        let marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        let circle = L.circle([lat, lng], {
            radius: parseInt(radiusInput.value || '{{ $location->geofence_radius }}', 10),
            color: '#1e5be0',
            fillColor: '#93c5fd',
            fillOpacity: 0.2,
            weight: 2
        }).addTo(map);

        function syncCircle(pos) {
            circle.setLatLng(pos);
            circle.setRadius(parseInt(radiusInput.value || '100', 10));
        }
        function updateInputs(p) {
            document.getElementById('latitude').value = p.lat.toFixed(7);
            document.getElementById('longitude').value = p.lng.toFixed(7);
            syncCircle(p);
        }
        marker.on('dragend', e => updateInputs(e.target.getLatLng()));
        map.on('click', e => { marker.setLatLng(e.latlng); updateInputs(e.latlng); });
        radiusInput.addEventListener('input', () => syncCircle(marker.getLatLng()));
        setTimeout(() => map.invalidateSize(), 120);
    </script>
    @endpush
</x-admin-layout>
