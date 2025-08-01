<?php

namespace App\Repositories;

use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportRepository implements ReportRepositoryInterface
{
    /**
     * Get all reports with optional filters and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllReports(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Report::with(['workspace', 'createdBy']);

        // Apply filters
        if (isset($filters['workspace_id'])) {
            $query->where('workspace_id', $filters['workspace_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_scheduled'])) {
            $query->where('is_scheduled', $filters['is_scheduled']);
        }

        if (isset($filters['created_by_id'])) {
            $query->where('created_by_id', $filters['created_by_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Find a report by ID.
     *
     * @param int $id
     * @return Report|null
     */
    public function findById(int $id): ?Report
    {
        return Report::with(['workspace', 'createdBy'])->find($id);
    }

    /**
     * Create a new report.
     *
     * @param array $data
     * @return Report
     */
    public function create(array $data): Report
    {
        return Report::create($data);
    }

    /**
     * Update a report.
     *
     * @param Report $report
     * @param array $data
     * @return Report
     */
    public function update(Report $report, array $data): Report
    {
        $report->update($data);
        return $report->fresh(['workspace', 'createdBy']);
    }

    /**
     * Delete a report.
     *
     * @param Report $report
     * @return bool
     */
    public function delete(Report $report): bool
    {
        return $report->delete();
    }

    /**
     * Get reports by workspace ID.
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getByWorkspace(int $workspaceId): Collection
    {
        return Report::where('workspace_id', $workspaceId)
            ->with(['createdBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get scheduled reports.
     *
     * @return Collection
     */
    public function getScheduledReports(): Collection
    {
        return Report::scheduled()
            ->with(['workspace', 'createdBy'])
            ->get();
    }

    /**
     * Update last generated timestamp.
     *
     * @param Report $report
     * @return Report
     */
    public function updateLastGenerated(Report $report): Report
    {
        $report->update(['last_generated_at' => now()]);
        return $report->fresh();
    }
}
