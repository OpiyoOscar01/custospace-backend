<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReportRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReportController extends Controller
{
    use AuthorizesRequests;
    /**
     * Create a new ReportController instance.
     */
    public function __construct(
        private ReportService $reportService
    ) {
        $this->authorizeResource(Report::class, 'report');
    }

    /**
     * Display a listing of reports.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only(['workspace_id', 'type', 'is_scheduled', 'created_by_id']);
        $perPage = $request->get('per_page', 15);

        $reports = $this->reportService->getAllReports($filters, $perPage);

        return ReportResource::collection($reports);
    }

    /**
     * Store a newly created report.
     *
     * @param CreateReportRequest $request
     * @return ReportResource
     */
    public function store(CreateReportRequest $request): ReportResource
    {
        $report = $this->reportService->createReport($request->validated());

        return new ReportResource($report);
    }

    /**
     * Display the specified report.
     *
     * @param Report $report
     * @return ReportResource
     */
    public function show(Report $report): ReportResource
    {
        return new ReportResource($report->load(['workspace', 'createdBy']));
    }

    /**
     * Update the specified report.
     *
     * @param UpdateReportRequest $request
     * @param Report $report
     * @return ReportResource
     */
    public function update(UpdateReportRequest $request, Report $report): ReportResource
    {
        $updatedReport = $this->reportService->updateReport($report, $request->validated());

        return new ReportResource($updatedReport);
    }

    /**
     * Remove the specified report.
     *
     * @param Report $report
     * @return JsonResponse
     */
    public function destroy(Report $report): JsonResponse
    {
        $this->reportService->deleteReport($report);

        return response()->json([
            'message' => 'Report deleted successfully',
        ]);
    }

    /**
     * Generate the specified report.
     *
     * @param Report $report
     * @return JsonResponse
     */
    public function generate(Report $report): JsonResponse
    {
        $this->authorize('generate', $report);

        $reportData = $this->reportService->generateReport($report);

        return response()->json([
            'message' => 'Report generated successfully',
            'data' => $reportData,
        ]);
    }

    /**
     * Duplicate the specified report.
     *
     * @param Report $report
     * @return ReportResource
     */
    public function duplicate(Report $report): ReportResource
    {
        $this->authorize('create', Report::class);

        $duplicateData = $report->toArray();
        unset($duplicateData['id'], $duplicateData['created_at'], $duplicateData['updated_at']);
        $duplicateData['name'] = $duplicateData['name'] . ' (Copy)';

        $duplicatedReport = $this->reportService->createReport($duplicateData);

        return new ReportResource($duplicatedReport);
    }
}
