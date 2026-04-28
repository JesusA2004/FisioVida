<?php

namespace App\Services\Audit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogService
{
    public function record(array $payload): void
    {
        DB::table('logs')->insert([
            'level' => 'audit',
            'actor_user_id' => $payload['user_id'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
            'action' => (string) ($payload['action'] ?? 'acción'),
            'module' => $payload['module'] ?? null,
            'entity_type' => $payload['auditable_type'] ?? null,
            'entity_id' => $payload['auditable_id'] ?? null,
            'auditable_type' => $payload['auditable_type'] ?? null,
            'auditable_id' => $payload['auditable_id'] ?? null,
            'message' => (string) ($payload['human_message'] ?? 'Acción registrada'),
            'human_message' => (string) ($payload['human_message'] ?? 'Acción registrada'),
            'old_values' => isset($payload['old_values']) ? json_encode($payload['old_values']) : null,
            'new_values' => isset($payload['new_values']) ? json_encode($payload['new_values']) : null,
            'ip' => $payload['ip_address'] ?? null,
            'ip_address' => $payload['ip_address'] ?? null,
            'user_agent' => isset($payload['user_agent']) ? substr((string) $payload['user_agent'], 0, 255) : null,
            'created_at' => now(),
        ]);
    }

    public function created(Request $request, string $module, string $auditableType, int|string|null $auditableId, string $humanMessage, array $newValues = []): void
    {
        $this->record([
            'user_id' => $request->user()?->id,
            'action' => 'Creación',
            'module' => $module,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'human_message' => $humanMessage,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function updated(Request $request, string $module, string $auditableType, int|string|null $auditableId, string $humanMessage, array $oldValues = [], array $newValues = []): void
    {
        $this->record([
            'user_id' => $request->user()?->id,
            'action' => 'Actualización',
            'module' => $module,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'human_message' => $humanMessage,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function deleted(Request $request, string $module, string $auditableType, int|string|null $auditableId, string $humanMessage, array $oldValues = []): void
    {
        $this->record([
            'user_id' => $request->user()?->id,
            'action' => 'Eliminación',
            'module' => $module,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'human_message' => $humanMessage,
            'old_values' => $oldValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function statusChanged(Request $request, string $module, string $auditableType, int|string|null $auditableId, string $humanMessage, string $from, string $to): void
    {
        $this->updated($request, $module, $auditableType, $auditableId, $humanMessage, ['status' => $from], ['status' => $to]);
    }

    public function completed(Request $request, string $module, string $auditableType, int|string|null $auditableId, string $humanMessage): void
    {
        $this->record([
            'user_id' => $request->user()?->id,
            'action' => 'Completado',
            'module' => $module,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'human_message' => $humanMessage,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function cancelled(Request $request, string $module, string $auditableType, int|string|null $auditableId, string $humanMessage): void
    {
        $this->record([
            'user_id' => $request->user()?->id,
            'action' => 'Cancelación',
            'module' => $module,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'human_message' => $humanMessage,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
