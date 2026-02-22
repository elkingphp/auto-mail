<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action to the audit logs.
     *
     * @param string $action Action name (e.g., 'preview', 'download', 'update')
     * @param string $resourceType Resource type (e.g., 'report', 'execution')
     * @param string|null $resourceId Resource UUID
     * @param array|null $oldValues Previous state
     * @param array|null $newValues New state
     * @return void
     */
    public function log(string $action, string $resourceType, ?string $resourceId = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        \App\Jobs\ProcessAuditLog::dispatch([
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Shortcut for logging a creation action.
     */
    public function logCreate(string $resourceType, string $resourceId, array $values): void
    {
        $this->log('create', $resourceType, $resourceId, null, $values);
    }

    /**
     * Shortcut for logging an update action.
     */
    public function logUpdate(string $resourceType, string $resourceId, array $oldValues, array $newValues): void
    {
        $this->log('update', $resourceType, $resourceId, $oldValues, $newValues);
    }

    /**
     * Shortcut for logging a deletion action.
     */
    public function logDelete(string $resourceType, string $resourceId, array $oldValues): void
    {
        $this->log('delete', $resourceType, $resourceId, $oldValues, null);
    }
}
