<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attendance extends Model
{
    protected $fillable = [
        'deployment_id',
        'security_personnel_id',
        'time_in',
        'time_in_latitude',
        'time_in_longitude',
        'time_in_distance_meters',
        'time_in_verified',
        'time_in_face_verified',
        'time_in_face_path',
        'time_in_face_score',
        'time_out',
        'time_out_latitude',
        'time_out_longitude',
        'time_out_distance_meters',
        'time_out_verified',
        'time_out_face_verified',
        'time_out_face_path',
        'time_out_face_score',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'time_in' => 'datetime',
            'time_out' => 'datetime',
            'time_in_latitude' => 'float',
            'time_in_longitude' => 'float',
            'time_out_latitude' => 'float',
            'time_out_longitude' => 'float',
            'time_in_verified' => 'boolean',
            'time_out_verified' => 'boolean',
            'time_in_face_verified' => 'boolean',
            'time_out_face_verified' => 'boolean',
            'time_in_face_score' => 'float',
            'time_out_face_score' => 'float',
        ];
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(SecurityPersonnel::class, 'security_personnel_id');
    }

    public static function formatDisplayTime(?Carbon $time): ?string
    {
        return $time?->format('g:i A');
    }

    public static function formatLateness(?int $minutes): ?string
    {
        if ($minutes === null || $minutes <= 0) {
            return null;
        }

        if ($minutes < 60) {
            return $minutes.' minute'.($minutes === 1 ? '' : 's');
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return $hours.' hour'.($hours === 1 ? '' : 's');
        }

        return $hours.' hour'.($hours === 1 ? '' : 's').' '.$remainingMinutes.' minute'.($remainingMinutes === 1 ? '' : 's');
    }

    public function checkInLatenessMinutes(Deployment $deployment): ?int
    {
        if (! $this->time_in) {
            return null;
        }

        $graceEndsAt = $deployment->checkInGraceEndsAt();

        if ($this->time_in->lte($graceEndsAt)) {
            return null;
        }

        return (int) $deployment->shiftStartAt()->diffInMinutes($this->time_in, true);
    }

    public function checkOutLatenessMinutes(Deployment $deployment): ?int
    {
        if (! $this->time_out) {
            return null;
        }

        $shiftEndsAt = $deployment->shiftEndAt();

        if ($this->time_out->lte($shiftEndsAt)) {
            return null;
        }

        return (int) $shiftEndsAt->diffInMinutes($this->time_out, true);
    }

    public function formattedDutyDuration(): ?string
    {
        if (! $this->time_in || ! $this->time_out) {
            return null;
        }

        $minutes = (int) $this->time_in->diffInMinutes($this->time_out, true);

        return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
    }

    public function timeInFaceUrl(): ?string
    {
        return $this->time_in_face_path
            ? Storage::disk('public')->url($this->time_in_face_path)
            : null;
    }

    public function timeOutFaceUrl(): ?string
    {
        return $this->time_out_face_path
            ? Storage::disk('public')->url($this->time_out_face_path)
            : null;
    }
}
