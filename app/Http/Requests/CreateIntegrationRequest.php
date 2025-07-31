<?php

namespace App\Http\Requests;

use App\Models\Integration;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Create Integration Request
 * 
 * Validates data for creating new integrations
 */
class CreateIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Integration::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'workspace_id' => 'required|exists:workspaces,id',
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:slack,github,gitlab,jira,discord,teams,bitbucket',
            'configuration' => 'required|array',
            'configuration.api_key' => 'sometimes|string',
            'configuration.webhook_url' => 'sometimes|url',
            'configuration.token' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'workspace_id.required' => 'A workspace is required for the integration.',
            'workspace_id.exists' => 'The selected workspace does not exist.',
            'type.in' => 'The integration type is not supported.',
            'configuration.required' => 'Integration configuration is required.',
        ];
    }
}
