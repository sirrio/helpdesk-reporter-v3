<?php

namespace App\Models;

use Database\Factories\DegreeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'faculty_id'])]
class Degree extends Model
{
    /** @use HasFactory<DegreeFactory> */
    use HasFactory, SoftDeletes;

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the attendances associated with the degree name.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'degree', 'name');
    }
}
