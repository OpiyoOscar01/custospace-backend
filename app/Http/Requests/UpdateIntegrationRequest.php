<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Integration Request
 * 
 * Validates data for updating existing integrations
 */
class UpdateIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('integration'));
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:slack,github,gitlab,jira,discord,teams,bitbucket',
            'configuration' => 'sometimes|array',
            'configuration.api_key' => 'sometimes|string',
            'configuration.webhook_url' => 'sometimes|url',
            'configuration.token' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
