<?php

namespace App\Services;

use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MaintenanceResponseFactory
{
    public function __construct(private readonly MaintenanceMode $maintenanceMode) {}

    public function make(Request $request): Response
    {
        $data = $this->maintenanceMode->active()
            ? $this->maintenanceMode->data()
            : [];
        $status = (int) ($data['status'] ?? 503);
        $headers = [];

        if (isset($data['retry'])) {
            $headers['Retry-After'] = (string) $data['retry'];
        }

        if (isset($data['refresh'])) {
            $headers['Refresh'] = (string) $data['refresh'];
        }

        if ($request->expectsJson()) {
            return response([
                'message' => 'Service Unavailable',
                'maintenance' => true,
            ], $status, $headers);
        }

        $template = $data['template'] ?? view('errors.maintenance', [
            'message' => 'Maintenance is currently in progress.',
            'plannedEndAt' => null,
        ])->render();

        return response($template, $status, $headers);
    }
}
