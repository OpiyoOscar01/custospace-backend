<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Custom Field Value Model
 * 
 * Stores the actual values for custom fields applied to various entities
 */
class CustomFieldValue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'custom_field_id',
        'entity_type',
        'entity_id',
        'value',
    ];

    /**
     * Get the custom field this value belongs to
     */
    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }

    /**
     * Get the entity that owns this custom field value
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter by custom field
     */
    public function scopeForCustomField($query, $customFieldId)
    {
        return $query->where('custom_field_id', $customFieldId);
    }

    /**
     * Scope to filter by entity
     */
    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)
                    ->where('entity_id', $entityId);
    }

    /**
     * Get formatted value based on custom field type
     */
    public function getFormattedValue()
    {
        $customField = $this->customField;
        
        if (!$customField) {
            return $this->value;
        }

        return match ($customField->type) {
            'number' => is_numeric($this->value) ? (float) $this->value : $this->value,
            'date' => $this->value ? \Carbon\Carbon::parse($this->value) : null,
            'checkbox' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'multiselect' => is_string($this->value) ? json_decode($this->value, true) : $this->value,
            default => $this->value,
        };
    }
}
