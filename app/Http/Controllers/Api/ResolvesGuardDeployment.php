<?php

namespace App\Http\Controllers\Api;

use App\Models\Deployment;
use Illuminate\Http\JsonResponse;

trait ResolvesGuardDeployment
{
    protected function resolveGuardDeployment(int $personnelId, ?int $deploymentId = null): ?Deployment
    {
        if ($deploymentId) {
            $deployment = Deployment::with(['location', 'attendance'])
                ->where('id', $deploymentId)
                ->where('security_personnel_id', $personnelId)
                ->assignable()
                ->first();

            if (! $deployment) {
                return null;
            }

            if ($deployment->deployment_date->isToday() || $deployment->isWithinShiftWindow()) {
                return $deployment;
            }

            return null;
        }

        return Deployment::resolveForPersonnel($personnelId);
    }

    protected function rejectInactiveDeployment(?Deployment $deployment): ?JsonResponse
    {
        if (! $deployment) {
            return response()->json(['message' => 'No deployment found for this action.'], 404);
        }

        if ($deployment->status === 'cancelled') {
            return response()->json(['message' => 'This deployment has been cancelled.'], 422);
        }

        if ($deployment->status === 'completed' && ! $deployment->isOnDuty()) {
            return response()->json(['message' => 'This deployment is already completed.'], 422);
        }

        return null;
    }
}
