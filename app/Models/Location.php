<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $fillable = [
        'name',
        'client_name',
        'address',
        'latitude',
        'longitude',
        'geofence_radius',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'geofence_radius' => 'integer',
        ];
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function localityFromAddress(): ?string
    {
        if (! filled($this->address)) {
            return null;
        }

        $segments = array_values(array_filter(array_map('trim', explode(',', $this->address))));

        if ($segments === []) {
            return null;
        }

        foreach ($segments as $segment) {
            if (preg_match('/\b(city|municipality|mun\.?)\b/i', $segment)) {
                return $segment;
            }
        }

        $last = $segments[array_key_last($segments)];

        if (count($segments) >= 2 && preg_match('/\b(cotabato|bukidnon|davao|mindanao|philippines|region)\b/i', $last)) {
            return $segments[count($segments) - 2];
        }

        return $last;
    }

    public function guardDisplayFields(): array
    {
        $area = filled($this->client_name) ? $this->client_name : null;
        $city = $this->localityFromAddress();
        $subtitle = collect([$area, $city])->filter()->implode(' · ');

        if ($subtitle === '' && filled($this->address)) {
            $subtitle = $this->address;
        }

        return [
            'post' => $this->name,
            'client' => $this->client_name,
            'area' => $area,
            'city' => $city,
            'address' => $this->address,
            'location_subtitle' => $subtitle ?: null,
        ];
    }
}
