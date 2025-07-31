<?php

namespace App\Services;

use App\Models\FormResponse;
use App\Models\Form;
use App\Repositories\Contracts\FormResponseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * Form Response Service
 * 
 * Handles business logic for form response operations
 */
class FormResponseService
{
    public function __construct(
        private FormResponseRepositoryInterface $formResponseRepository
    ) {}

    /**
     * Get all form responses with optional filters
     */
    public function getAllResponses(array $filters = []): Collection
    {
        return $this->formResponseRepository->all($filters);
    }

    /**
     * Get paginated form responses
     */
    public function getPaginatedResponses(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->formResponseRepository->paginate($filters, $perPage);
    }

    /**
     * Find form response by ID
     */
    public function findResponse(int $id): ?FormResponse
    {
        return $this->formResponseRepository->find($id);
    }

    /**
     * Create a new form response
     */
    public function createResponse(array $data, ?Request $request = null): FormResponse
    {
        // Add request metadata if available
        if ($request) {
            $data['ip_address'] = $request->ip();
            $data['user_agent'] = $request->userAgent();
        }

        // Process and validate response data
        $data['data'] = $this->processResponseData($data['data'], $data['form_id']);

        return $this->formResponseRepository->create($data);
    }

    /**
     * Update an existing form response
     */
    public function updateResponse(FormResponse $formResponse, array $data): FormResponse
    {
        // Process and validate response data if provided
        if (isset($data['data'])) {
            $data['data'] = $this->processResponseData($data['data'], $formResponse->form_id);
        }

        return $this->formResponseRepository->update($formResponse, $data);
    }

    /**
     * Delete a form response
     */
    public function deleteResponse(FormResponse $formResponse): bool
    {
        return $this->formResponseRepository->delete($formResponse);
    }

    /**
     * Get responses for a specific form
     */
    public function getFormResponses(int $formId, array $filters = []): Collection
    {
        return $this->formResponseRepository->getByForm($formId, $filters);
    }

    /**
     * Get responses by a specific user
     */
    public function getUserResponses(int $userId, array $filters = []): Collection
    {
        return $this->formResponseRepository->getByUser($userId, $filters);
    }

    /**
     * Get form statistics
     */
    public function getFormStatistics(Form $form): array
    {
        return $this->formResponseRepository->getFormStatistics($form);
    }

    /**
     * Export form responses to CSV
     */
    public function exportResponsesToCsv(Form $form): string
    {
        $responses = $this->getFormResponses($form->id);
        $fields = $form->fields;

        // Create CSV headers
        $headers = ['ID', 'Submitted At', 'User'];
        foreach ($fields as $field) {
            $headers[] = $field['label'];
        }

        // Create CSV rows
        $rows = [];
        $rows[] = $headers;

        foreach ($responses as $response) {
            $row = [
                $response->id,
                $response->created_at->format('Y-m-d H:i:s'),
                $response->user ? $response->user->name : 'Anonymous'
            ];

            foreach ($fields as $field) {
                $value = $response->data[$field['name']] ?? '';
                
                // Handle array values (checkboxes)
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                $row[] = $value;
            }

            $rows[] = $row;
        }

        // Convert to CSV string
        $csv = '';
        foreach ($rows as $row) {
            $csv .= '"' . implode('","', $row) . '"' . "\n";
        }

        return $csv;
    }

    /**
     * Get response analytics for a form
     */
    public function getResponseAnalytics(Form $form): array
    {
        $responses = $this->getFormResponses($form->id);
        $analytics = [
            'total_responses' => $responses->count(),
            'completion_rate' => 100, // Assuming all responses are complete
            'field_analytics' => [],
        ];

        // Analyze each field
        foreach ($form->fields as $field) {
            $fieldName = $field['name'];
            $fieldData = $responses->map(function ($response) use ($fieldName) {
                return $response->data[$fieldName] ?? null;
            })->filter();

            $fieldAnalytics = [
                'field_name' => $fieldName,
                'field_label' => $field['label'],
                'field_type' => $field['type'],
                'response_count' => $fieldData->count(),
                'completion_rate' => $responses->count() > 0 ? ($fieldData->count() / $responses->count()) * 100 : 0,
            ];

            // Type-specific analytics
            switch ($field['type']) {
                case 'select':
                case 'radio':
                    $fieldAnalytics['value_distribution'] = $fieldData->countBy();
                    break;
                case 'checkbox':
                    $allValues = $fieldData->flatten();
                    $fieldAnalytics['value_distribution'] = $allValues->countBy();
                    break;
                case 'number':
                    $numbers = $fieldData->filter(function ($value) {
                        return is_numeric($value);
                    });
                    if ($numbers->count() > 0) {
                        $fieldAnalytics['average'] = $numbers->avg();
                        $fieldAnalytics['min'] = $numbers->min();
                        $fieldAnalytics['max'] = $numbers->max();
                    }
                    break;
            }

            $analytics['field_analytics'][] = $fieldAnalytics;
        }

        return $analytics;
    }

    /**
     * Process and validate response data
     */
    private function processResponseData(array $data, int $formId): array
    {
        $form = Form::findOrFail($formId);
        $processedData = [];

        foreach ($form->fields as $field) {
            $fieldName = $field['name'];
            $value = $data[$fieldName] ?? null;

            // Process based on field type
            switch ($field['type']) {
                case 'checkbox':
                    $processedData[$fieldName] = is_array($value) ? $value : [];
                    break;
                case 'number':
                    $processedData[$fieldName] = is_numeric($value) ? (float)$value : null;
                    break;
                default:
                    $processedData[$fieldName] = $value;
            }
        }

        return $processedData;
    }
}
