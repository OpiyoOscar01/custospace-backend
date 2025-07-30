<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Class RecurringTask
 * 
 * Represents a recurring task configuration
 * 
 * @property int $id
 * @property int $task_id
 * @property string $frequency
 * @property int $interval
 * @property array|null $days_of_week
 * @property int|null $day_of_month
 * @property Carbon $next_due_date
 * @property Carbon|null $end_date
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Task $task
 */
class RecurringTask extends Model
{
    use HasFactory;

    /**
     * Available frequency options.
     */
    public const FREQUENCY_DAILY = 'daily';
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_YEARLY = 'yearly';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'frequency',
        'interval',
        'days_of_week',
        'day_of_month',
        'next_due_date',
        'end_date',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'days_of_week' => 'array',
        'next_due_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'interval' => 'integer',
        'day_of_month' => 'integer',
    ];

    /**
     * Get the task that this recurring configuration belongs to.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get all available frequency options.
     */
    public static function getFrequencyOptions(): array
    {
        return [
            self::FREQUENCY_DAILY => 'Daily',
            self::FREQUENCY_WEEKLY => 'Weekly',
            self::FREQUENCY_MONTHLY => 'Monthly',
            self::FREQUENCY_YEARLY => 'Yearly',
        ];
    }

    /**
     * Check if the recurring task is due.
     */
    public function isDue(): bool
    {
        return $this->is_active && 
               $this->next_due_date <= now() && 
               (!$this->end_date || $this->end_date >= now());
    }

    /**
     * Calculate the next due date based on frequency and interval.
     */
    public function calculateNextDueDate(): Carbon
    {
        $nextDate = $this->next_due_date->copy();

        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                $nextDate->addDays($this->interval);
                break;
            case self::FREQUENCY_WEEKLY:
                $nextDate->addWeeks($this->interval);
                break;
            case self::FREQUENCY_MONTHLY:
                $nextDate->addMonths($this->interval);
                if ($this->day_of_month) {
                    $nextDate->day($this->day_of_month);
                }
                break;
            case self::FREQUENCY_YEARLY:
                $nextDate->addYears($this->interval);
                break;
        }

        return $nextDate;
    }

    /**
     * Update the next due date.
     */
    public function updateNextDueDate(): void
    {
        $this->next_due_date = $this->calculateNextDueDate();
        $this->save();
    }

    /**
     * Scope to get active recurring tasks only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get due recurring tasks.
     */
    public function scopeDue($query)
    {
        return $query->active()
                    ->where('next_due_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                          ->orWhere('end_date', '>=', now());
                    });
    }

    /**
     * Scope to filter by frequency.
     */
    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }
}
