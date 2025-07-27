<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorkspaceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:workspaces,slug,' . $this->id,
            'description' => 'nullable|string',
            'logo' => 'nullable|string',
            'domain' => 'nullable|string',
            'settings' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }
}

