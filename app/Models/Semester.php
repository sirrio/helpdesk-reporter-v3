<?php

namespace App\Models;

use Database\Factories\SemesterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['semester', 'start', 'end'])]
class Semester extends Model
{
    /** @use HasFactory<SemesterFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attendances associated with the semester label.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'semester', 'semester');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start' => 'date',
            'end' => 'date',
            'deleted_at' => 'datetime',
        ];
    }
}
