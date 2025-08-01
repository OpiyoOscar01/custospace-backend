<?php

namespace App\Repositories\Contracts;

use App\Models\Report;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface ReportRepositoryInterface
{
    /**
     * Get all reports with optional filters and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllReports(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a report by ID.
     *
     * @param int $id
     * @return Report|null
     */
    public function findById(int $id): ?Report;

    /**
     * Create a new report.
     *
     * @param array $data
     * @return Report
     */
    public function create(array $data): Report;

    /**
     * Update a report.
     *
     * @param Report $report
     * @param array $data
     * @return Report
     */
    public function update(Report $report, array $data): Report;

    /**
     * Delete a report.
     *
     * @param Report $report
     * @return bool
     */
    public function delete(Report $report): bool;

    /**
     * Get reports by workspace ID.
     *
     * @param int $workspaceId
     * @return Collection
     */
    public function getByWorkspace(int $workspaceId): Collection;

    /**
     * Get scheduled reports.
     *
     * @return Collection
     */
    public function getScheduledReports(): Collection;

    /**
     * Update last generated timestamp.
     *
     * @param Report $report
     * @return Report
     */
    public function updateLastGenerated(Report $report): Report;
}
