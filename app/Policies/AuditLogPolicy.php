<?php
// app/Policies/AuditLogPolicy.php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Audit Log Authorization Policy
 * 
 * Defines authorization rules for audit log operations
 */
class AuditLogPolicy
{
    /**
     * Determine whether the user can view any audit logs.
     */
    public function viewAny(User $user): bool
    {
        // Only users with audit permissions can view audit logs
        return $user->hasAnyPermission(['view_audit_logs', 'manage_audit_logs']) ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the audit log.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        // Users can view audit logs if they have permission or are admin
        return $user->hasAnyPermission(['view_audit_logs', 'manage_audit_logs']) ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create audit logs.
     */
    public function create(User $user): bool
    {
        // System can create audit logs, or users with manage permission
        return $user->hasAnyPermission(['create_audit_logs', 'manage_audit_logs']) ||
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the audit log.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        // Generally, audit logs should not be updated for integrity
        // Only allow for admins in exceptional cases
        return $user->hasRole('admin') && $user->hasPermission('manage_audit_logs');
    }

    /**
     * Determine whether the user can delete the audit log.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        // Generally, audit logs should not be deleted for compliance
        // Only allow for system admins with explicit permission
        return $user->hasRole('admin') && $user->hasPermission('delete_audit_logs');
    }

    /**
     * Determine whether the user can view sensitive information.
     */
    public function viewSensitive(User $user, AuditLog $auditLog): bool
    {
        // Only admins or users with special permission can view sensitive data
        return $user->hasRole('admin') || $user->hasPermission('view_sensitive_audit_data');
    }

    /**
     * Determine whether the user can cleanup old audit logs.
     */
    public function cleanup(User $user): bool
    {
        // Only system admins can cleanup old audit logs
        return $user->hasRole('admin') && $user->hasPermission('cleanup_audit_logs');
    }
}
