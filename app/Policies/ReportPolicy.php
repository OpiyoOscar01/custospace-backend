<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReportPolicy
{
    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view reports in their accessible workspaces
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, Report $report): bool
    {
        // Users can view reports in workspaces they have access to
        return $user->workspaces()->where('workspaces.id', $report->workspace_id)->exists();
    }

    /**
     * Determine whether the user can create reports.
     */
    public function create(User $user): bool
    {
        return true; // Authenticated users can create reports
    }

    /**
     * Determine whether the user can update the report.
     */
    public function update(User $user, Report $report): bool
    {
        // Users can update reports they created or if they're workspace admin
        return $report->created_by_id === $user->id || 
               $user->workspaces()
                   ->where('workspaces.id', $report->workspace_id)
                   ->wherePivot('role', 'admin')
                   ->exists();
    }

    /**
     * Determine whether the user can delete the report.
     */
    public function delete(User $user, Report $report): bool
    {
        // Users can delete reports they created or if they're workspace admin
        return $report->created_by_id === $user->id || 
               $user->workspaces()
                   ->where('workspaces.id', $report->workspace_id)
                   ->wherePivot('role', 'admin')
                   ->exists();
    }

    /**
     * Determine whether the user can generate the report.
     */
    public function generate(User $user, Report $report): bool
    {
        // Users can generate reports in workspaces they have access to
        return $user->workspaces()->where('workspaces.id', $report->workspace_id)->exists();
    }

    /**
     * Determine whether the user can restore the report.
     */
    public function restore(User $user, Report $report): bool
    {
        return $this->delete($user, $report);
    }

    /**
     * Determine whether the user can permanently delete the report.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return $this->delete($user, $report);
    }
}
