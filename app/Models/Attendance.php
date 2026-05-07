<?php

namespace App\Models;

use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'user_id',
    'semester',
    'date',
    'startTime',
    'endTime',
    'degree',
    'faculty',
    'mathBasic',
    'mathFractions',
    'mathLow',
    'mathHigh',
    'programming',
    'physics',
    'chemistry',
    'organization',
    'online',
])]
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var array<string, string>
     */
    public const TOPIC_OPTIONS = [
        'mathBasic' => 'Mathe Grundlagen',
        'mathFractions' => 'Mathe Bruchrechnung',
        'mathLow' => 'Mathe leicht',
        'mathHigh' => 'Mathe anspruchsvoll',
        'programming' => 'Programmierung',
        'physics' => 'Physik',
        'chemistry' => 'Chemie',
        'organization' => 'Organisation',
    ];

    /**
     * Get the user that owns the attendance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the semester entry matching the attendance label.
     */
    public function semesterEntry(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester', 'semester');
    }

    /**
     * Get the degree entry matching the attendance label.
     */
    public function degreeEntry(): BelongsTo
    {
        return $this->belongsTo(Degree::class, 'degree', 'name');
    }

    /**
     * Get the faculty entry matching the attendance label.
     */
    public function facultyEntry(): BelongsTo
    {
        return $this->belongsTo(Faculty::class, 'faculty', 'name');
    }

    /**
     * Apply common attendance filters to the query.
     *
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when(
                filled($filters['semester'] ?? null),
                fn (Builder $builder) => $builder->where('semester', $filters['semester']),
            )
            ->when(
                filled($filters['degree'] ?? null),
                fn (Builder $builder) => $builder->where('degree', $filters['degree']),
            )
            ->when(
                filled($filters['faculty'] ?? null),
                fn (Builder $builder) => $builder->where('faculty', $filters['faculty']),
            )
            ->when(
                filled($filters['topic'] ?? null),
                fn (Builder $builder) => $builder->where($filters['topic'], true),
            )
            ->when(
                array_key_exists('online', $filters) && $filters['online'] !== null && $filters['online'] !== '',
                fn (Builder $builder) => $builder->where('online', (bool) $filters['online']),
            )
            ->when(
                filled($filters['from'] ?? null),
                fn (Builder $builder) => $builder->whereDate('date', '>=', $filters['from']),
            )
            ->when(
                filled($filters['until'] ?? null),
                fn (Builder $builder) => $builder->whereDate('date', '<=', $filters['until']),
            );
    }

    /**
     * Get the configured topic labels.
     *
     * @return array<string, string>
     */
    public static function topicOptions(): array
    {
        return self::TOPIC_OPTIONS;
    }

    /**
     * Get the active topic labels for the attendance.
     *
     * @return array<int, string>
     */
    public function topicLabels(): array
    {
        return collect(self::TOPIC_OPTIONS)
            ->filter(fn (string $label, string $column) => (bool) $this->{$column})
            ->values()
            ->all();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'mathBasic' => 'boolean',
            'mathFractions' => 'boolean',
            'mathLow' => 'boolean',
            'mathHigh' => 'boolean',
            'programming' => 'boolean',
            'physics' => 'boolean',
            'chemistry' => 'boolean',
            'organization' => 'boolean',
            'online' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }
}
