<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DutyPing extends Model
{
    protected $fillable = [
        'client_uuid',
        'security_personnel_id',
        'deployment_id',
        'latitude',
        'longitude',
        'distance_meters',
        'within_geofence',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'within_geofence' => 'boolean',
            'captured_at' => 'datetime',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(SecurityPersonnel::class, 'security_personnel_id');
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }
}
